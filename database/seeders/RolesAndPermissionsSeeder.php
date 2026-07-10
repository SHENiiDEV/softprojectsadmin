<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $managerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $curatorRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'curator', 'guard_name' => 'web']);
        $workerRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'worker', 'guard_name' => 'web']);

        // Create a default admin user
        $admin = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@projecthub.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $admin->assignRole($adminRole);

        // Optional: Create a test user for each role to facilitate testing
        $manager = \App\Models\User::create([
            'name' => 'Manager User',
            'email' => 'manager@projecthub.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $manager->assignRole($managerRole);

        $curator = \App\Models\User::create([
            'name' => 'Curator User',
            'email' => 'curator@projecthub.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $curator->assignRole($curatorRole);

        $worker = \App\Models\User::create([
            'name' => 'Worker User',
            'email' => 'worker@projecthub.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $worker->assignRole($workerRole);
    }
}
