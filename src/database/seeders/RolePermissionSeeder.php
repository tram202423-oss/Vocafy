<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        Role::firstOrCreate(['name' => RoleEnum::SUPER_ADMIN->value]);
        Role::firstOrCreate(['name' => RoleEnum::ADMIN->value]);
        Role::firstOrCreate(['name' => RoleEnum::EDITOR->value]);
        Role::firstOrCreate(['name' => RoleEnum::MODERATOR->value]);
        Role::firstOrCreate(['name' => RoleEnum::USER->value]);

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [

            // Category
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',

            // Topic
            'view topics',
            'create topics',
            'edit topics',
            'delete topics',

            // Vocabulary
            'view vocabularies',
            'create vocabularies',
            'edit vocabularies',
            'delete vocabularies',

            // Lesson
            'view lessons',
            'create lessons',
            'edit lessons',
            'delete lessons',

            // Quiz
            'view quizzes',
            'create quizzes',
            'edit quizzes',
            'delete quizzes',

            // User
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Role
            'manage roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
            ]);
        }

        $superAdmin = Role::findByName(RoleEnum::SUPER_ADMIN->value);

        $superAdmin->givePermissionTo(
            Permission::all()
        );
    }
}