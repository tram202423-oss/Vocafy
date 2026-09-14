<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            [
                'email' => 'admin@vocafy.com',
            ],
            [
                'name' => 'SuperAdmin',
                'password' => bcrypt('12345678'),
            ]
        );

        $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    }
}
