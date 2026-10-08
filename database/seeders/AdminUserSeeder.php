<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');
        $password = config('admin.password');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD are not set in .env; skipping admin user.');

            return;
        }

        User::query()->updateOrCreate(
            ['email' => strtolower(trim($email))],
            [
                'type' => User::TYPE_ADMIN,
                'name' => config('admin.name'),
                'password' => $password,
                'status' => 'active',
            ],
        );

        $this->command?->info("Admin user ready: {$email}");
    }
}
