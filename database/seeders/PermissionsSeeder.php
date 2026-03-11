<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * SOLO crea permisos.
 * No toca roles ni asignaciones.
 *
 * Lista ÚNICA y GRANULAR: cada permiso usado con can() en el código
 * debe estar aquí. Si agregas un nuevo can('x') en un recurso Filament,
 * agrega 'x' a la lista correspondiente.
 */
class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // ── Usuarios y acceso ──────────────────────────
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // ── Catálogos (página hub) ─────────────────────
            'catalogs.view',

            // ── Colores ───────────────────────────────────
            'colors.view',
            'colors.create',
            'colors.edit',
            'colors.delete',

            // ── Unidades de medida ─────────────────────────
            'uom.view',
            'uom.create',
            'uom.edit',
            'uom.delete',

            // ── Turnos ────────────────────────────────────
            'shifts.view',
            'shifts.create',
            'shifts.edit',
            'shifts.delete',

            // ── Bodegas ───────────────────────────────────
            'warehouses.view',
            'warehouses.create',
            'warehouses.edit',
            'warehouses.delete',

            // ── Camiones ──────────────────────────────────
            'trucks.view',
            'trucks.create',
            'trucks.edit',
            'trucks.delete',

            // ── Productos (materia prima + terminado) ─────
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            // ── Producción ────────────────────────────────
            'production.view',
            'production.create',
            'production.edit',
            'production.delete',
            'production.confirm',

            // ── Inventario (página hub + movimientos) ─────
            'inventory.view',
            'inventory_movements.view',
            'inventory_movements.create',

            // ── Bitácora / Auditoría ──────────────────────
            'audit.view',

            // ── Ventas ────────────────────────────────────
            'sales.view',
            'sales.create',
            'sales.edit',
            'sales.confirm',
            'sales.cancel',

            // ── Facturas (Invoices) ───────────────────────
            'invoices.view',

            // ── Pedidos (Orders) ──────────────────────────
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.delete',

            // ── Devoluciones (Returns) ────────────────────
            'order_returns.view',
            'order_returns.create',
            'order_returns.delete',

            // ── Despachos (Dispatches) ─────────────────────
            'dispatches.view',
            'dispatches.create',
            'dispatches.edit',
            'dispatches.delete',
            'dispatches.start',
            'dispatches.complete',
            'dispatches.deliver',
            'reports.all',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name'       => $perm,
                'guard_name' => 'web',
            ]);
        }
    }
}
