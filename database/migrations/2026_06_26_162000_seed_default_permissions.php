<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
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

        // Assign to default roles
        $admin = Role::findOrCreate('admin', 'web');
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        $manager = Role::findOrCreate('manager', 'web');
        if ($manager) {
            $manager->givePermissionTo([
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
        }

        $curator = Role::findOrCreate('curator', 'web');
        if ($curator) {
            $curator->givePermissionTo([
                'view_projects',
                'view_tasks',
                'manage_tasks',
                'view_deadlines',
                'view_calendar',
                'view_activity',
                'view_credentials',
                'manage_users',
            ]);
        }

        $worker = Role::findOrCreate('worker', 'web');
        if ($worker) {
            $worker->givePermissionTo([
                'view_projects',
                'view_tasks',
                'manage_tasks',
                'view_deadlines',
                'view_calendar',
                'view_activity',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
