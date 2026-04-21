<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\InventoryMovement;
use App\Models\Production;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Encontrar movimientos de producción cuyos padres ya no existen
        $orphanedMovements = InventoryMovement::where('source_type', 'production')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('productions')
                    ->whereRaw('productions.id = inventory_movements.source_id');
            })
            ->get();

        foreach ($orphanedMovements as $movement) {
            try {
                // Borrar el movimiento activará el InventoryMovementObserver que revierte el stock
                // asegurando que el inventario vuelva a coincidir con las producciones existentes.
                $movement->delete();
            } catch (\Exception $e) {
                Log::error("Error limpiando movimiento huérfano {$movement->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hay vuelta atrás para una limpieza de huérfanos
    }
};
