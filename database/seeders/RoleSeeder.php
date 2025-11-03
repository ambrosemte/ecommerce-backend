<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles
        Role::create(['name' => RoleEnum::ADMIN]);
        Role::create(['name' => RoleEnum::AGENT]);
        Role::create(['name' => RoleEnum::SELLER]);
        Role::create(['name' => RoleEnum::USER]);
    }
}
