<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;
use Carbon\Carbon;

class CleanupAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-audit-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina registros de la bitácora con más de 1 mes de antigüedad';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $oneMonthAgo = Carbon::now()->subMonth();
        
        $count = AuditLog::where('created_at', '<', $oneMonthAgo)->delete();

        if ($count > 0) {
            $this->info("Se han eliminado {$count} registros antiguos de la bitácora.");
        } else {
            $this->info("No se encontraron registros antiguos para eliminar.");
        }
    }
}
