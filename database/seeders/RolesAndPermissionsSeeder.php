<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions list
        $permissions = [
            'view_projects',
            'manage_projects',
            'view_tasks',
            'manage_tasks',
            'view_deadlines',
            'view_calendar',
            'view_activity',
            'view_credentials',
            'manage_credentials',
            'view_reports',
            'manage_users',
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        // Create roles and assign permissions
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions($permissions);

        $managerRole = Role::findOrCreate('manager', 'web');
        $managerRole->syncPermissions([
            'view_projects',
            'manage_projects',
            'view_tasks',
            'manage_tasks',
            'view_deadlines',
            'view_calendar',
            'view_activity',
            'view_credentials',
            'manage_credentials',
            'view_reports',
        ]);

        $curatorRole = Role::findOrCreate('curator', 'web');
        $curatorRole->syncPermissions([
            'view_projects',
            'view_tasks',
            'manage_tasks',
            'view_deadlines',
            'view_calendar',
            'view_activity',
            'view_credentials',
            'manage_users',
        ]);

        $workerRole = Role::findOrCreate('worker', 'web');
        $workerRole->syncPermissions([
            'view_projects',
            'view_tasks',
            'manage_tasks',
            'view_deadlines',
            'view_calendar',
            'view_activity',
        ]);

        // Create default users (idempotent, using updateOrCreate)
        $admin = User::updateOrCreate(
            ['email' => 'admin@projecthub.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles([$adminRole]);

        $manager = User::updateOrCreate(
            ['email' => 'manager@projecthub.com'],
            [
                'name' => 'Manager User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $manager->syncRoles([$managerRole]);

        $curator = User::updateOrCreate(
            ['email' => 'curator@projecthub.com'],
            [
                'name' => 'Curator User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $curator->syncRoles([$curatorRole]);

        $worker = User::updateOrCreate(
            ['email' => 'worker@projecthub.com'],
            [
                'name' => 'Worker User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $worker->syncRoles([$workerRole]);
    }
}
