<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SetAdminEmail extends Command
{
    protected $signature = 'admin:set-email {username} {email}';

    protected $description = 'Gắn email khôi phục cho tài khoản quản trị đã có';

    public function handle(): int
    {
        $user = User::where('username', Str::lower(trim($this->argument('username'))))->where('is_admin', true)->first();
        if (! $user) {
            $this->error('Không tìm thấy tài khoản quản trị.');

            return self::FAILURE;
        }

        $email = trim($this->argument('email'));
        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email:rfc', 'max:255', 'not_regex:/\.invalid$/i', Rule::unique('users')->ignore($user)],
        ]);
        if ($validator->fails()) {
            $this->error('Email không hợp lệ hoặc đã được tài khoản khác sử dụng.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($user, $email): void {
            Password::broker('users')->deleteToken($user);
            $user->forceFill(['email' => $email, 'email_verified_at' => null])->save();
        });
        $this->info('Đã cập nhật email khôi phục. Mật khẩu và quyền quản trị được giữ nguyên.');

        return self::SUCCESS;
    }
}
