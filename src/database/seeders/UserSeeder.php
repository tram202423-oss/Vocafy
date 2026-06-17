<?php

namespace Database\Seeders;

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
                'name' => 'Admin',
                'password' => bcrypt('12345678'),
            ]
        );

        $admin->assignRole('admin');
    }
}
