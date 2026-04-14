<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users & Access
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.assign',

            // Catalogs
            'catalogs.view', 'catalogs.create', 'catalogs.edit', 'catalogs.delete',

            // Warehouses & Trucks
            'warehouses.view', 'warehouses.create', 'warehouses.edit', 'warehouses.delete',
            'trucks.view', 'trucks.dispatch', 'trucks.return',

            // Inventory
            'inventory.view', 'inventory.in', 'inventory.out', 'inventory.transfer', 'inventory.adjust',

            // Production
            'production.view', 'production.create', 'production.edit',

            // Purchases
            'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.export',

            // Sales
            'sales.view', 'sales.create', 'sales.edit', 'sales.export',

            // Reports & Dashboard
            'reports.view', 'dashboard.view',

            // Audit logs
            'audit.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Roles (must exist from RolesSeeder)
        $superAdmin = Role::where('name', 'super_admin')->first();
        $admin      = Role::where('name', 'admin')->first();
        $warehouse  = Role::where('name', 'warehouse')->first();
        $sales      = Role::where('name', 'sales')->first();
        $accounting = Role::where('name', 'accounting')->first();
        $viewer     = Role::where('name', 'viewer')->first();

        // Super Admin: all permissions
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        // Admin: almost all, but no deleting users by default (you can change)
        if ($admin) {
            $admin->syncPermissions([
                'dashboard.view', 'reports.view', 'audit.view',

                'users.view', 'users.create', 'users.edit',
                'roles.view', 'roles.assign',

                'catalogs.view', 'catalogs.create', 'catalogs.edit', 'catalogs.delete',

                'warehouses.view', 'warehouses.create', 'warehouses.edit', 'warehouses.delete',
                'trucks.view', 'trucks.dispatch', 'trucks.return',

                'inventory.view', 'inventory.in', 'inventory.out', 'inventory.transfer', 'inventory.adjust',

                'production.view', 'production.create', 'production.edit',

                'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.export',

                'sales.view', 'sales.create', 'sales.edit', 'sales.export',
            ]);
        }

        // Warehouse operator (bodeguero)
        if ($warehouse) {
            $warehouse->syncPermissions([
                'dashboard.view',
                'warehouses.view',
                'inventory.view', 'inventory.in', 'inventory.out', 'inventory.transfer',
                'production.view', 'production.create',
                'trucks.view', 'trucks.dispatch', 'trucks.return',
            ]);
        }

        // Sales operator (vendedor)
        if ($sales) {
            $sales->syncPermissions([
                'dashboard.view',
                'sales.view', 'sales.create',
                'inventory.view',
                'trucks.view',
            ]);
        }

        // Accounting
        if ($accounting) {
            $accounting->syncPermissions([
                'dashboard.view',
                'reports.view',
                'sales.view', 'sales.export',
                'purchases.view', 'purchases.export',
                'audit.view',
            ]);
        }

        // Viewer (read-only)
        if ($viewer) {
            $viewer->syncPermissions([
                'dashboard.view',
                'reports.view',
                'inventory.view',
                'sales.view',
                'purchases.view',
                'warehouses.view',
                'audit.view',
            ]);
        }
    }
}
