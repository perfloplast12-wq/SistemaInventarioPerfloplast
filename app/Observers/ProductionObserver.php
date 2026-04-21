<?php

namespace App\Observers;

use App\Models\Production;
use App\Models\InventoryMovement;

class ProductionObserver
{
    /**
     * Handle the Production "deleting" event.
     * Si se elimina una producción, debemos limpiar sus movimientos de inventario.
     * El InventoryMovementObserver se encargará de revertir el stock al borrar el movimiento.
     */
    public function deleting(Production $production): void
    {
        InventoryMovement::where('source_type', 'production')
            ->where('source_id', $production->id)
            ->get()
            ->each(fn ($movement) => $movement->delete());
    }
}
