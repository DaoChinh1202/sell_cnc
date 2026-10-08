<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ChinhAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ChinhAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_admin_with_hashed_interactive_password(): void
    {
        $this->artisan('db:seed', ['--class' => ChinhAdminSeeder::class, '--force' => true])
            ->expectsQuestion('Nhập mật khẩu cho admin chinhcn', 'Test-only-secret!9')
            ->expectsOutput('Đã tạo admin chinhcn với email chinhcn2312@gmail.com.')
            ->assertSuccessful();

        $user = User::where('username', 'chinhcn')->sole();
        $this->assertTrue($user->is_admin);
        $this->assertSame('chinhcn2312@gmail.com', $user->email);
        $this->assertTrue(Hash::check('Test-only-secret!9', $user->password));
    }

    public function test_existing_account_is_not_overwritten_or_promoted(): void
    {
        $user = User::factory()->create(['username' => 'chinhcn', 'is_admin' => false]);
        $before = $user->fresh()->getAttributes();

        (new ChinhAdminSeeder)->run();

        $this->assertSame($before, $user->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 1);
    }

    public function test_email_conflict_does_not_change_existing_account(): void
    {
        $user = User::factory()->admin()->create(['email' => 'chinhcn2312@gmail.com']);
        $before = $user->fresh()->getAttributes();

        try {
            (new ChinhAdminSeeder)->run();
            $this->fail('Expected email conflict.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('đang được tài khoản khác sử dụng', $exception->getMessage());
        }

        $this->assertSame($before, $user->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 1);
    }

    public function test_missing_password_does_not_create_account(): void
    {
        try {
            (new ChinhAdminSeeder)->run();
            $this->fail('Expected missing password error.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ít nhất 8 ký tự', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }
}
