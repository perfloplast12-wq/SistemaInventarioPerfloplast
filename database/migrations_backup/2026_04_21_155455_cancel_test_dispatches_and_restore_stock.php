<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Dispatch;
use App\Services\DispatchService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ejecutar limpieza de datos de prueba
        $service = app(DispatchService::class);
        $dispatches = Dispatch::where('status', 'in_progress')->get();

        foreach ($dispatches as $dispatch) {
            try {
                $service->cancel($dispatch);
            } catch (\Exception $e) {
                // Silencioso en migración para no bloquear el despliegue
                Log::error("Error cancelando despacho {$dispatch->id} en migración: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hay vuelta atrás para una limpieza
    }
};
