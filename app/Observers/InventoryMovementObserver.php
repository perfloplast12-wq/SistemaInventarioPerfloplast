<?php

namespace App\Observers;

use App\Models\InventoryMovement;
use App\Services\StockService;

class InventoryMovementObserver
{
    public function created(InventoryMovement $movement): void
    {
        app(StockService::class)->apply($movement);
    }

    public function updating(InventoryMovement $movement): void
    {
        // Revertir el estado anterior antes de guardar el nuevo
        $old = $movement->replicate();
        $old->fill($movement->getOriginal());

        app(StockService::class)->revert($old);
    }

    public function updated(InventoryMovement $movement): void
    {
        app(StockService::class)->apply($movement);
    }

    public function deleted(InventoryMovement $movement): void
    {
        app(StockService::class)->revert($movement);
    }
}
