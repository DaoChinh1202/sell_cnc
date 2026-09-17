<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Add more accounts here using unique, lowercase usernames.
        // Existing accounts (including their passwords and permissions) are preserved.
        $accounts = [
            [
                'username' => 'duyhoangadmin',
                'name' => 'Duy Hoàng Admin',
                'password' => 'duyhoang88@!',
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrNew(['username' => $account['username']]);

            if ($user->exists) {
                continue;
            }

            $user->forceFill([
                'name' => $account['name'],
                // Internal placeholder only; authentication uses username, not email.
                'email' => $account['username'].'@admin.invalid',
                'password' => $account['password'], // Hashed by the User model cast.
                'is_admin' => true,
            ])->save();
        }
    }
}
