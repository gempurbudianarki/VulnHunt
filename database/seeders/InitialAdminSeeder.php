<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('INITIAL_ADMIN_EMAIL', 'admin@ubbg.ac.id');
        $password = env('INITIAL_ADMIN_PASSWORD', 'AdminUBBG2026!Secure');
        $name = env('INITIAL_ADMIN_NAME', 'Administrator Lomba');

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'rules_accepted_version' => '1',
                'rules_accepted_at' => now(),
                'rules_accepted_ip' => '127.0.0.1',
                'is_active' => true,
            ]
        );

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
