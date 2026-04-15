<?php
/**
 * ONE-TIME FIX: Drop the strict UNIQUE constraint on unit_of_measures.name
 * so soft-deleted records don't block new inserts.
 * 
 * Visit: https://perfloplast.azurewebsites.net/fix-unique.php
 * DELETE THIS FILE after running it once.
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    // Boot Laravel
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(\Illuminate\Http\Request::capture());

    $db = \Illuminate\Support\Facades\DB::connection();

    echo "=== FIX UNIQUE CONSTRAINT ===\n\n";

    // 1. Check if the constraint still exists
    $indexes = $db->select("SHOW INDEX FROM unit_of_measures WHERE Key_name = 'unit_of_measures_name_unique'");

    if (empty($indexes)) {
        echo "✅ El constraint ya fue eliminado. No hay nada que hacer.\n";
    } else {
        echo "⚠️  Constraint encontrado. Eliminando...\n";

        // Drop the unique constraint
        $db->statement("ALTER TABLE unit_of_measures DROP INDEX unit_of_measures_name_unique");
        echo "✅ Constraint UNIQUE eliminado.\n";

        // Add a normal index for performance
        $db->statement("CREATE INDEX unit_of_measures_name_index ON unit_of_measures(name)");
        echo "✅ Índice normal creado.\n";
    }

    // 2. Show current soft-deleted records
    echo "\n=== REGISTROS SOFT-DELETED (fantasmas) ===\n";
    $ghostRecords = $db->select("SELECT id, name, deleted_at FROM unit_of_measures WHERE deleted_at IS NOT NULL");

    if (empty($ghostRecords)) {
        echo "No hay registros eliminados.\n";
    } else {
        foreach ($ghostRecords as $r) {
            echo "  ID={$r->id} | name='{$r->name}' | deleted_at={$r->deleted_at}\n";
        }
        echo "\nEstos registros estaban bloqueando la creación de nuevos con el mismo nombre.\n";
    }

    // 3. Show active records
    echo "\n=== REGISTROS ACTIVOS ===\n";
    $activeRecords = $db->select("SELECT id, name, abbreviation, is_active FROM unit_of_measures WHERE deleted_at IS NULL");

    if (empty($activeRecords)) {
        echo "No hay registros activos.\n";
    } else {
        foreach ($activeRecords as $r) {
            echo "  ID={$r->id} | name='{$r->name}' | abbr='{$r->abbreviation}' | active={$r->is_active}\n";
        }
    }

    // 4. Also run pending migrations
    echo "\n=== RUNNING PENDING MIGRATIONS ===\n";
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();

    echo "\n✅ ¡LISTO! Ahora puedes crear 'unidad' sin problemas.\n";
    echo "⚠️  ELIMINA ESTE ARCHIVO (fix-unique.php) por seguridad.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
