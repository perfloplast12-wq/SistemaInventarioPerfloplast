<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * SOLO crea roles y les asigna permisos.
 * No crea permisos nuevos — esos vienen de PermissionsSeeder.
 *
 * PermissionsSeeder DEBE correrse ANTES que este seeder.
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Crear roles ─────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin      = Role::firstOrCreate(['name' => 'admin',       'guard_name' => 'web']);
        $warehouse  = Role::firstOrCreate(['name' => 'warehouse',   'guard_name' => 'web']);
        $sales      = Role::firstOrCreate(['name' => 'sales',       'guard_name' => 'web']);
        $accounting = Role::firstOrCreate(['name' => 'accounting',  'guard_name' => 'web']);
        $production = Role::firstOrCreate(['name' => 'production',  'guard_name' => 'web']);
        $viewer     = Role::firstOrCreate(['name' => 'viewer',      'guard_name' => 'web']);
        $conductor  = Role::firstOrCreate(['name' => 'conductor',   'guard_name' => 'web']);

        // ── 2. Super Admin: TODOS los permisos ─────────────
        $superAdmin->syncPermissions(Permission::all());

        // ── 3. Admin: todo menos users.delete ──────────────
        $admin->syncPermissions(Permission::where('name', '!=', 'users.delete')->get());

        // ── 4. Bodeguero (Warehouse) ───────────────────────
        // Gestión de stock, camiones y catálogos base.
        $warehouse->syncPermissions([
            'catalogs.view',
            'warehouses.view', 'warehouses.create', 'warehouses.edit',
            'trucks.view', 'trucks.create', 'trucks.edit',
            'uom.view', 'uom.create', 'uom.edit',
            'shifts.view', 'shifts.create', 'shifts.edit',
            'products.view', 'products.create', 'products.edit',
            'inventory.view',
            'inventory_movements.view', 'inventory_movements.create',
            'colors.view', 'colors.create', 'colors.edit',
            'dispatches.view',
        ]);

        // ── 5. Vendedor (Sales) ────────────────────────────
        // Ventas y Pedidos.
        $sales->syncPermissions([
            'catalogs.view',
            'products.view',
            'inventory.view',
            'sales.view', 'sales.create', 'sales.edit', 'sales.confirm',
            'orders.view', 'orders.create', 'orders.edit',
            'order_returns.view', 'order_returns.create',
            'invoices.view',
        ]);

        // ── 6. Producción (Production) ─────────────────────
        // Órdenes de producción y materias primas.
        $production->syncPermissions([
            'catalogs.view',
            'products.view', 'products.create', 'products.edit',
            'inventory.view',
            'production.view', 'production.create', 'production.edit', 'production.confirm',
            'colors.view', 'colors.create', 'colors.edit',
        ]);

        // ── 7. Contabilidad (Accounting) ────────────────────
        // Auditoría y consulta financiera.
        $accounting->syncPermissions([
            'catalogs.view',
            'products.view',
            'inventory.view',
            'audit.view',
            'sales.view',
            'orders.view',
            'dispatches.view',
            'invoices.view',
            'reports.all',
        ]);

        // ── 8. Conductor (Piloto) ──────────────────────────
        // Solo ver lo que necesita entregar.
        $conductor->syncPermissions([
            'catalogs.view',
            'orders.view',
            'dispatches.view',
            'dispatches.deliver',
        ]);

        // ── 9. Viewer (solo lectura total) ──────────────────
        $viewer->syncPermissions([
            'catalogs.view',
            'warehouses.view',
            'trucks.view',
            'uom.view',
            'shifts.view',
            'products.view',
            'inventory.view',
            'audit.view',
            'sales.view',
            'orders.view',
            'dispatches.view',
            'invoices.view',
        ]);
    }
}
