<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Everybody who approves links may check them; the list itself is managed by admin, manager and curator.
     *
     * @var array<string, list<string>>
     */
    private array $rolePermissions = [
        'admin' => ['view_scam_links', 'manage_scam_links'],
        'manager' => ['view_scam_links', 'manage_scam_links'],
        'curator' => ['view_scam_links', 'manage_scam_links'],
        'worker' => ['view_scam_links'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->rolePermissions as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::whereIn('name', ['view_scam_links', 'manage_scam_links'])->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
