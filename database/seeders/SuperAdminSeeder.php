<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('SUPERADMIN_EMAIL', ''));
        $password = (string) env('SUPERADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            $this->command?->warn(
                'Superadmin not created: set SUPERADMIN_EMAIL and SUPERADMIN_PASSWORD before running db:seed.'
            );

            return;
        }

        // Do not silently replace or reset an existing superadmin account.
        if (User::where('role', 'superadmin')->exists()) {
            return;
        }

        User::create([
            'first_name' => 'Super',
            'last_name'  => 'Admin',
            'email'      => $email,
            'password'   => Hash::make($password),
            'email_verified_at' => now(),

            // 🔐 CRITICAL
            'role' => 'superadmin',
            'status' => 'active',
        ]);
    }
}