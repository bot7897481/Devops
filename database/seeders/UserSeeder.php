<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = User::create([
            'email' => 'admin@local39.org',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super_admin');

        // Create Test Admin
        $admin = User::create([
            'email' => 'testadmin@local39.org',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Create Test Chief
        $chief = User::create([
            'email' => 'chief@local39.org',
            'password' => Hash::make('password'),
            'role' => 'chief',
            'email_verified_at' => now(),
        ]);
        $chief->assignRole('chief');

        // Create Test Applicant
        $applicant = User::create([
            'email' => 'applicant@local39.org',
            'password' => Hash::make('password'),
            'role' => 'applicant',
            'email_verified_at' => now(),
        ]);
        $applicant->assignRole('applicant');
    }
}
