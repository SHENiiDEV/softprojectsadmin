<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Permissions introduced for Clients, PCI DSS and Tariffs modules, mapped to default roles.
     *
     * @var array<string, array<int, string>>
     */
    private array $rolePermissions = [
        'admin' => ['view_clients', 'manage_clients', 'view_pci_dss', 'manage_pci_dss', 'view_tariffs', 'manage_tariffs'],
        'manager' => ['view_clients', 'manage_clients', 'view_pci_dss', 'manage_pci_dss', 'view_tariffs', 'manage_tariffs'],
        'curator' => ['view_clients', 'manage_clients', 'view_pci_dss', 'view_tariffs'],
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

        foreach (['view_clients', 'manage_clients', 'view_pci_dss', 'manage_pci_dss', 'view_tariffs', 'manage_tariffs'] as $permission) {
            Permission::where('name', $permission)->where('guard_name', 'web')->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
