<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AdminResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
    }

    private function payload(User $user, string $token): array
    {
        return ['email' => $user->email, 'token' => $token, 'password' => 'NewPassword88@!', 'password_confirmation' => 'NewPassword88@!'];
    }

    public function test_guest_can_open_recovery_forms_and_login_links_to_them(): void
    {
        $this->get(route('signin'))->assertOk()->assertSee(route('password.request'), false);
        $this->get(route('password.request'))->assertOk()->assertSee('name="username"', false)->assertSee('name="_token"', false);
        $this->get(route('password.reset', ['token' => 'example-token', 'email' => 'admin@example.com']))
            ->assertOk()->assertSee('name="password_confirmation"', false)
            ->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_only_admin_receives_link_with_same_public_response_for_other_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['username' => 'regular-member']);
        $placeholder = User::factory()->admin()->create(['email' => 'admin@admin.invalid']);
        $message = null;
        foreach ([$admin->username, $member->username, $placeholder->username, 'unknown-admin'] as $username) {
            $this->from(route('password.request'))->post(route('password.email'), ['username' => strtoupper($username)])
                ->assertRedirect(route('password.request'))->assertSessionHas('status')->assertSessionHasNoErrors();
            $message ??= session('status');
            $this->assertSame($message, session('status'));
        }
        Notification::assertSentTo($admin, AdminResetPassword::class, function ($notification) use ($admin): bool {
            $stored = \DB::table('password_reset_tokens')->where('email', $admin->email)->value('token');
            $this->assertNotSame($notification->token, $stored);
            $this->assertTrue(Hash::check($notification->token, $stored));

            return true;
        });
        Notification::assertNotSentTo($member, AdminResetPassword::class);
        Notification::assertNotSentTo($placeholder, AdminResetPassword::class);
        Notification::assertCount(1);
    }

    public function test_reset_changes_password_rotates_remember_token_and_consumes_link(): void
    {
        Event::fake([PasswordReset::class]);
        $admin = User::factory()->admin()->create(['remember_token' => 'previous-token']);
        $token = Password::broker('users')->createToken($admin);
        $payload = $this->payload($admin, $token);
        $this->post(route('password.update'), $payload)->assertRedirect(route('signin'))->assertSessionHas('status');
        $this->assertTrue(Hash::check($payload['password'], $admin->fresh()->password));
        $this->assertNotSame('previous-token', $admin->fresh()->remember_token);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $admin->email]);
        Event::assertDispatched(PasswordReset::class);
        $this->assertGuest();
        $this->post(route('password.update'), $payload)->assertSessionHasErrors('email');
        $this->post(route('signin.store'), ['username' => $admin->username, 'password' => 'password'])->assertSessionHasErrors('username');
        $this->post(route('signin.store'), ['username' => $admin->username, 'password' => $payload['password']])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_invalid_expired_and_wrong_account_tokens_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $token = Password::broker('users')->createToken($admin);
        $this->post(route('password.update'), $this->payload($admin, 'invalid'))->assertSessionHasErrors('email');
        $this->post(route('password.update'), $this->payload($other, $token))->assertSessionHasErrors('email');
        $this->travel(61)->minutes();
        $this->post(route('password.update'), $this->payload($admin, $token))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
        $this->assertTrue(Hash::check('password', $other->fresh()->password));
    }

    public function test_non_admin_cannot_reset_even_with_valid_token_or_forged_admin_flag(): void
    {
        $user = User::factory()->create();
        $token = Password::broker('users')->createToken($user);
        $this->post(route('password.update'), $this->payload($user, $token) + ['is_admin' => true])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_revoked_admin_cannot_use_a_previously_issued_link(): void
    {
        $user = User::factory()->admin()->create();
        $token = Password::broker('users')->createToken($user);
        $user->forceFill(['is_admin' => false])->save();
        $this->post(route('password.update'), $this->payload($user, $token))->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_submissions_are_rate_limited(): void
    {
        $user = User::factory()->admin()->create();
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('password.update'), $this->payload($user, 'invalid'))->assertSessionHasErrors('email');
        }
        $this->post(route('password.update'), $this->payload($user, 'invalid'))->assertStatus(429);
    }

    public function test_password_validation_keeps_token_usable_and_does_not_flash_passwords(): void
    {
        $admin = User::factory()->admin()->create();
        $token = Password::broker('users')->createToken($admin);
        foreach (['short', 'lowercaseonly', 'NewPassword88@!'] as $password) {
            $data = $this->payload($admin, $token);
            $data['password'] = $password;
            $data['password_confirmation'] = $password === 'NewPassword88@!' ? 'mismatch' : $password;
            $this->post(route('password.update'), $data)->assertSessionHasErrors('password')
                ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        }
        $this->assertTrue(Password::broker('users')->tokenExists($admin, $token));
    }

    public function test_link_requests_are_throttled_per_account_and_ip(): void
    {
        $admin = User::factory()->admin()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.email'), ['username' => $admin->username])->assertSessionHas('status');
        }
        Notification::assertSentToTimes($admin, AdminResetPassword::class, 1);
        $this->post(route('password.email'), ['username' => 'other'])->assertStatus(429);
        $this->travel(61)->seconds();
        $this->post(route('password.email'), ['username' => $admin->username])->assertSessionHas('status');
        Notification::assertSentToTimes($admin, AdminResetPassword::class, 2);
    }

    public function test_email_link_uses_configured_origin_and_encoded_email(): void
    {
        config(['app.url' => 'https://khomau3d.example:8443']);
        $admin = User::factory()->admin()->create(['email' => 'admin+cnc@example.com']);
        $notification = new AdminResetPassword('reset-token');
        $mail = $notification->toMail($admin);
        $this->assertSame('https://khomau3d.example:8443/admin/reset-password/reset-token?email=admin%2Bcnc%40example.com', $mail->actionUrl);
        $this->assertStringContainsString('60 phút', implode(' ', $mail->outroLines));
        $this->assertStringContainsString('Đặt lại mật khẩu', $mail->render());
    }

    public function test_email_setup_preserves_password_and_permissions_and_invalidates_old_link(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@admin.invalid']);
        $hash = $admin->password;
        Password::broker('users')->createToken($admin);
        $this->artisan('admin:set-email', ['username' => $admin->username, 'email' => 'owner@example.com'])->assertSuccessful();
        $this->assertSame('owner@example.com', $admin->fresh()->email);
        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertTrue($admin->fresh()->is_admin);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'admin@admin.invalid']);
        foreach (['bad-email', 'admin@admin.invalid'] as $email) {
            $this->artisan('admin:set-email', ['username' => $admin->username, 'email' => $email])->assertFailed();
        }
        $member = User::factory()->create(['username' => 'regular-member']);
        $this->artisan('admin:set-email', ['username' => $admin->username, 'email' => $member->email])->assertFailed();
        $this->artisan('admin:set-email', ['username' => $member->username, 'email' => 'member@example.com'])->assertFailed();
    }

    public function test_old_authenticated_session_is_rejected_after_password_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $oldHash = $admin->password;
        $admin->forceFill(['password' => 'NewPassword88@!'])->save();
        $this->actingAs($admin)->withSession(['password_hash_web' => $oldHash])
            ->get(route('inventory'))->assertRedirect(route('signin'));
        $this->assertGuest();
    }

    public function test_recovery_posts_require_csrf(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $this->post(route('password.email'), ['username' => 'admin'])->assertStatus(419);
        $this->post(route('password.update'), [])->assertStatus(419);
    }
}
