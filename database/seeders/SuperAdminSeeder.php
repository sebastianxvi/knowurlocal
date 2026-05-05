<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 🧠 Prevent duplicate superadmin
        if (User::where('role', 'superadmin')->exists()) {
            return;
        }

        User::create([
            'first_name' => 'Super',
            'last_name'  => 'Admin',
            'email'      => env('SUPERADMIN_EMAIL'),
            'password'   => Hash::make(env('SUPERADMIN_PASSWORD')),
            'email_verified_at' => now(),

            // 🔐 CRITICAL
            'role' => 'superadmin',
            'status' => 'active',
        ]);
    }
}