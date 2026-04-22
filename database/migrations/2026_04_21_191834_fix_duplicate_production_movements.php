<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Production;
use App\Models\InventoryMovement;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Limpiar duplicados de entrada de productos terminados
        $productions = Production::where('status', 'confirmed')->get();
        
        foreach ($productions as $production) {
            $movements = InventoryMovement::where('source_type', 'production')
                ->where('source_id', $production->id)
                ->where('type', 'in')
                ->orderBy('created_at', 'asc')
                ->get();
            
            if ($movements->count() > 1) {
                // Mantener el primer movimiento, eliminar los duplicados
                // El Observer de InventoryMovement se encargará de restar el stock sobrante
                $movements->slice(1)->each(function ($m) {
                    $m->delete();
                });
            }
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
