<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UpdateDuyHoangAdminEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tests\TestCase;

class UpdateDuyHoangAdminEmailSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMockingConsoleOutput();
    }

    public function test_updates_email_preserves_credentials_and_invalidates_reset_token(): void
    {
        $user = User::factory()->admin()->create([
            'username' => 'duyhoangadmin',
            'email' => 'chinhcn2312@gmail.com',
        ]);
        $password = $user->password;
        $rememberToken = $user->remember_token;
        Password::broker('users')->createToken($user);

        $this->seed(UpdateDuyHoangAdminEmailSeeder::class);

        $user->refresh();
        $this->assertSame('hoangdriver2000@gmail.com', $user->email);
        $this->assertSame('hoangdriver2000@gmail.com', $user->username);
        $this->assertDatabaseMissing('users', ['username' => 'duyhoangadmin']);
        $this->assertSame($password, $user->password);
        $this->assertSame($rememberToken, $user->remember_token);
        $this->assertTrue($user->is_admin);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'chinhcn2312@gmail.com']);

        $this->seed(UpdateDuyHoangAdminEmailSeeder::class);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($password, $user->fresh()->password);
    }

    public function test_duplicate_email_is_rejected_without_modifying_the_admin(): void
    {
        $user = User::factory()->admin()->create(['username' => 'duyhoangadmin']);
        User::factory()->create(['email' => 'hoangdriver2000@gmail.com']);
        $before = $user->fresh()->getAttributes();

        try {
            $this->seed(UpdateDuyHoangAdminEmailSeeder::class);
            $this->fail('Expected email conflict.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('đã được tài khoản khác sử dụng', $exception->getMessage());
        }

        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    public function test_duplicate_username_does_not_change_email_or_delete_reset_token(): void
    {
        $user = User::factory()->admin()->create(['username' => 'duyhoangadmin']);
        User::factory()->create(['username' => 'hoangdriver2000@gmail.com']);
        $before = $user->fresh()->getAttributes();
        $token = Password::broker('users')->createToken($user);

        try {
            $this->seed(UpdateDuyHoangAdminEmailSeeder::class);
            $this->fail('Expected username conflict.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Username mới đã được tài khoản khác sử dụng', $exception->getMessage());
        }

        $this->assertSame($before, $user->fresh()->getAttributes());
        $this->assertTrue(Password::broker('users')->tokenExists($user, $token));
    }

    public function test_admin_with_previously_updated_email_can_be_renamed_and_log_in(): void
    {
        $user = User::factory()->admin()->create([
            'username' => 'duyhoangadmin',
            'email' => 'hoangdriver2000@gmail.com',
        ]);

        $this->seed(UpdateDuyHoangAdminEmailSeeder::class);

        $this->post(route('signin.store'), [
            'username' => 'hoangdriver2000@gmail.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_missing_admin_is_not_created(): void
    {
        try {
            $this->seed(UpdateDuyHoangAdminEmailSeeder::class);
            $this->fail('Expected missing admin error.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Không tìm thấy tài khoản quản trị', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }
}
