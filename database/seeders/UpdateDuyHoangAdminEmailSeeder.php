<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;

class UpdateDuyHoangAdminEmailSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $username = 'hoangdriver2000@gmail.com';
            $user = User::query()->where('username', 'duyhoangadmin')->where('is_admin', true)->lockForUpdate()->first();

            if (! $user) {
                if (User::query()->where('username', $username)->where('email', $username)->where('is_admin', true)->exists()) {
                    $this->command?->info('Username và email đã được cập nhật; không thay đổi tài khoản.');

                    return;
                }

                throw new RuntimeException('Không tìm thấy tài khoản quản trị duyhoangadmin.');
            }

            if (User::query()->where('username', $username)->exists()) {
                throw new RuntimeException('Username mới đã được tài khoản khác sử dụng.');
            }

            // Reuse email validation and reset-token invalidation from the admin command.
            $buffer = new BufferedOutput;
            $status = Artisan::call('admin:set-email', [
                'username' => $user->username,
                'email' => $username,
            ], $buffer);

            if ($status !== 0) {
                throw new RuntimeException(trim($buffer->fetch()));
            }

            $user->refresh()->forceFill(['username' => $username])->save();
            $this->command?->info('Đã đổi username và email thành '.$username.'. Giữ nguyên mật khẩu và quyền quản trị.');
        });
    }
}
