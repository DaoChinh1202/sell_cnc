<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class ChinhAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('username', 'chinhcn')->exists()) {
            $this->command?->warn('Tài khoản chinhcn đã tồn tại; không thay đổi thông tin hoặc quyền.');

            return;
        }

        if (User::query()->where('email', 'chinhcn2312@gmail.com')->exists()) {
            throw new RuntimeException('Email chinhcn2312@gmail.com đang được tài khoản khác sử dụng. Hãy cập nhật email tài khoản đó trước khi chạy lại; seeder không tự thay đổi tài khoản cũ.');
        }

        $password = $this->command?->secret('Nhập mật khẩu cho admin chinhcn');

        if (! is_string($password) || strlen($password) < 8) {
            throw new RuntimeException('Cần nhập mật khẩu ít nhất 8 ký tự. Chạy seeder trong terminal tương tác.');
        }

        (new User)->forceFill([
            'name' => 'chinhcn',
            'username' => 'chinhcn',
            'email' => 'chinhcn2312@gmail.com',
            'password' => $password,
            'is_admin' => true,
        ])->save();

        $this->command?->info('Đã tạo admin chinhcn với email chinhcn2312@gmail.com.');
    }
}
