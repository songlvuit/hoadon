<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Tạo tài khoản quản trị đầu tiên từ biến môi trường (chạy lại nhiều lần vẫn an toàn). */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('Bỏ qua tạo admin: chưa đặt ADMIN_EMAIL / ADMIN_PASSWORD trong .env');

            return;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => env('ADMIN_NAME', 'Quản trị'), 'password' => $password, 'is_active' => true],
        );

        $this->command?->info($user->wasRecentlyCreated
            ? "Đã tạo tài khoản quản trị {$email}"
            : "Tài khoản {$email} đã có sẵn, không thay đổi.");
    }
}
