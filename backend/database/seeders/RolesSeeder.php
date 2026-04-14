<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin      = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $warehouse  = Role::firstOrCreate(['name' => 'warehouse', 'guard_name' => 'web']);
        $sales      = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $accounting = Role::firstOrCreate(['name' => 'accounting', 'guard_name' => 'web']);
        $viewer     = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        // 2) Permisos del sistema
        $perms = [
            // Usuarios / seguridad
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.assign',

            // 5.1 Unidades de medida
            'uom.view',
            'uom.create',
            'uom.edit',

            // 5.2 Turnos
            'shifts.view',
            'shifts.create',
            'shifts.edit',

             // Bodegas
            'warehouses.view',
            'warehouses.create',
            'warehouses.edit',
            'warehouses.delete',

            // Camiones
            'trucks.view',
            'trucks.create',
            'trucks.edit',
            'trucks.delete',

            // Inventario (panel)
            'inventory.view',

            // Productos - Materia prima
            'raw_products.view',
            'raw_products.create',
            'raw_products.edit',
            'raw_products.delete',

            // Productos - Producto terminado
            'finished_products.view',
            'finished_products.create',
            'finished_products.edit',
            'finished_products.delete',



        ];

        // 3) Crear permisos
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // 4) Asignación
        // Super Admin y Admin: todos los permisos
        $superAdmin->syncPermissions($perms);
        $admin->syncPermissions($perms);

        // Roles operativos: solo vista (catálogos)
        $readCatalogs = ['uom.view', 'shifts.view'];

        $warehouse->syncPermissions($readCatalogs);
        $sales->syncPermissions($readCatalogs);
        $accounting->syncPermissions($readCatalogs);
        $viewer->syncPermissions($readCatalogs);
    }
}
