<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_username_and_registration_is_unavailable(): void
    {
        $this->get('/admin/signin')->assertOk()
            ->assertSee('name="username"', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('Đăng ký');
        $this->get('/admin/signup')->assertNotFound();
        $this->post('/admin/signup')->assertStatus(405);
        $this->get('/admin/logout')->assertNotFound();
    }

    public function test_all_admin_management_routes_require_authentication_and_admin_permission(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'admin')
                || in_array($route->getName(), ['signin', 'signin.store', 'logout'])) {
                continue;
            }

            $this->assertContains('auth', $route->gatherMiddleware(), $route->uri());
            $this->assertContains('admin', $route->gatherMiddleware(), $route->uri());
        }

        foreach ([
            ['GET', '/admin'],
            ['GET', '/admin/inventory'],
            ['GET', '/admin/create-product'],
            ['GET', '/admin/products/1/edit'],
            ['POST', '/admin/products'],
            ['PUT', '/admin/products/1'],
            ['POST', '/admin/categories'],
            ['PUT', '/admin/categories/1'],
            ['DELETE', '/admin/categories/1'],
        ] as [$method, $uri]) {
            $this->call($method, $uri)->assertRedirect(route('signin'));
        }
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_admin_can_log_in_and_return_to_the_requested_page(): void
    {
        $admin = User::factory()->admin()->create();
        $this->get('/admin/inventory')->assertRedirect(route('signin'));
        $oldSession = session()->getId();

        $this->post(route('signin.store'), [
            'username' => $admin->username,
            'password' => 'password',
        ])->assertRedirect(route('inventory'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotSame($oldSession, session()->getId());
        $this->get('/admin/inventory')->assertOk()->assertSee('Đăng xuất');
        $this->get('/admin')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/admin/signin')->assertRedirect(route('dashboard'));
    }

    public function test_login_rejects_invalid_passwords_unknown_users_and_non_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['username' => 'member']);

        foreach ([
            [$admin->username, 'incorrect'],
            ['unknown', 'password'],
            [$member->username, 'password'],
        ] as [$username, $password]) {
            $this->from(route('signin'))->post(route('signin.store'), compact('username', 'password'))
                ->assertRedirect(route('signin'))
                ->assertSessionHasErrors(['username' => 'Tên đăng nhập hoặc mật khẩu không đúng.'])
                ->assertSessionMissing('_old_input.password');
            $this->assertGuest();
        }
    }

    public function test_authenticated_non_admin_is_denied_but_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')->assertForbidden();
        $this->post('/admin/products')->assertForbidden();
        $this->get('/admin/signin')->assertRedirect(route('home'));
        $this->post('/admin/logout')->assertRedirect(route('signin'));
        $this->assertGuest();
    }

    public function test_failed_logins_are_throttled_and_recover_after_a_minute(): void
    {
        $admin = User::factory()->admin()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/admin/signin', [
                'username' => $admin->username,
                'password' => 'wrong',
            ])->assertSessionHasErrors('username');
        }

        $credentials = ['username' => strtoupper($admin->username), 'password' => 'password'];
        $this->post('/admin/signin', $credentials)->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->assertStringContainsString('quá nhiều lần', session('errors')->first('username'));

        $this->travel(61)->seconds();
        $this->post('/admin/signin', $credentials)->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_logout_invalidates_session_and_remember_cookie(): void
    {
        $admin = User::factory()->admin()->create();
        $cookieName = auth()->guard('web')->getRecallerName();
        $login = $this->post('/admin/signin', [
            'username' => $admin->username,
            'password' => 'password',
            'remember' => '1',
        ])->assertCookie($cookieName);
        $this->withCookie($cookieName, $login->getCookie($cookieName)->getValue());
        $this->withSession(['private-marker' => 'private']);
        $oldToken = session()->token();
        $oldSession = session()->getId();

        $this->post('/admin/logout')->assertRedirect(route('signin'))
            ->assertCookieExpired($cookieName)->assertSessionMissing('private-marker');
        $this->assertGuest();
        $this->assertNotSame($oldToken, session()->token());
        $this->assertNotSame($oldSession, session()->getId());
        $this->get('/admin')->assertRedirect(route('signin'));
    }

    public function test_auth_forms_require_csrf_tokens_outside_tests(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $this->post('/admin/signin', ['username' => 'admin', 'password' => 'password'])
            ->assertStatus(419);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post('/admin/logout')->assertStatus(419);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_seeder_creates_requested_account_without_demo_data(): void
    {
        $this->seed(AdminUserSeeder::class);
        $admin = User::where('username', 'duyhoangadmin')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('duyhoang88@!', $admin->password));
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('products', 0);

        $this->post('/admin/signin', [
            'username' => 'duyhoangadmin',
            'password' => 'duyhoang88@!',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);

        $admin->forceFill(['password' => 'changed-password', 'is_admin' => false])->save();
        $this->seed(AdminUserSeeder::class);
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('changed-password', $admin->fresh()->password));
        $this->assertFalse($admin->fresh()->is_admin);
    }
}
