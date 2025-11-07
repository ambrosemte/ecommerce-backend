<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Contracts\Role;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // User
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'], // Check by email
            [
                'name' => 'Regular User',
                'password' => Hash::make('password123'),
                'phone' => '080' . rand(10000000, 99999999), // 11-digit phone
            ]
        );
        $user->assignRole(RoleEnum::USER);

        // Seller
        $seller = User::firstOrCreate(
            ['email' => 'seller@example.com'],
            [
                'name' => 'Seller User',
                'password' => Hash::make('password123'),
                'phone' => '080' . rand(10000000, 99999999),
            ]
        );
        $seller->assignRole(RoleEnum::SELLER);

        // Agent
        $seller = User::firstOrCreate(
            ['email' => 'agent@example.com'],
            [
                'name' => 'Seller Agent',
                'password' => Hash::make('password123'),
                'phone' => '080' . rand(10000000, 99999999),
            ]
        );
        $seller->assignRole(RoleEnum::AGENT);

        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'phone' => '080' . rand(10000000, 99999999),
            ]
        );
        $admin->assignRole(RoleEnum::ADMIN);

    }
}
