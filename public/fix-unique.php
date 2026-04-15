<?php
/**
 * ONE-TIME FIX: Creates missing roles and runs pending migrations.
 * Visit: https://perfloplast.azurewebsites.net/fix-unique.php
 * DELETE THIS FILE after running it once.
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(\Illuminate\Http\Request::capture());

    $db = \Illuminate\Support\Facades\DB::connection();

    echo "=== FIX: UNIQUE CONSTRAINTS ===\n\n";

    // 1. Fix unit_of_measures constraint
    $indexes = $db->select("SHOW INDEX FROM unit_of_measures WHERE Key_name = 'unit_of_measures_name_unique'");
    if (empty($indexes)) {
        echo "✅ unit_of_measures: constraint ya eliminado.\n";
    } else {
        $db->statement("ALTER TABLE unit_of_measures DROP INDEX unit_of_measures_name_unique");
        $db->statement("CREATE INDEX unit_of_measures_name_index ON unit_of_measures(name)");
        echo "✅ unit_of_measures: constraint eliminado.\n";
    }

    // 2. Fix users email constraint (if exists and model uses SoftDeletes)
    $userIndexes = $db->select("SHOW INDEX FROM users WHERE Key_name = 'users_email_unique'");
    if (!empty($userIndexes)) {
        $db->statement("ALTER TABLE users DROP INDEX users_email_unique");
        $db->statement("CREATE INDEX users_email_index ON users(email)");
        echo "✅ users: constraint unique eliminado (soft-delete compatible).\n";
    } else {
        echo "✅ users: constraint ya fue procesado.\n";
    }

    echo "\n=== FIX: MISSING ROLES ===\n\n";

    // 3. Create all roles that should exist
    $requiredRoles = [
        'super_admin', 'admin', 'warehouse', 'sales',
        'accounting', 'production', 'viewer', 'conductor', 'mantenimiento'
    ];

    foreach ($requiredRoles as $roleName) {
        $exists = $db->select("SELECT id FROM roles WHERE name = ? AND guard_name = 'web'", [$roleName]);
        if (empty($exists)) {
            $db->insert("INSERT INTO roles (name, guard_name, created_at, updated_at) VALUES (?, 'web', NOW(), NOW())", [$roleName]);
            echo "  ✅ Rol '$roleName' creado.\n";
        } else {
            echo "  ✓ Rol '$roleName' ya existe.\n";
        }
    }

    echo "\n=== REGISTROS SOFT-DELETED ===\n";
    
    // Unit of measures
    $ghostUom = $db->select("SELECT id, name, deleted_at FROM unit_of_measures WHERE deleted_at IS NOT NULL");
    if (!empty($ghostUom)) {
        echo "\nunit_of_measures:\n";
        foreach ($ghostUom as $r) {
            echo "  ID={$r->id} | name='{$r->name}' | deleted_at={$r->deleted_at}\n";
        }
    }

    // Users
    $ghostUsers = $db->select("SELECT id, name, email, deleted_at FROM users WHERE deleted_at IS NOT NULL");
    if (!empty($ghostUsers)) {
        echo "\nusers:\n";
        foreach ($ghostUsers as $r) {
            echo "  ID={$r->id} | name='{$r->name}' | email='{$r->email}' | deleted_at={$r->deleted_at}\n";
        }
    }

    echo "\n=== RUNNING PENDING MIGRATIONS ===\n";
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();

    // Clear permission cache
    echo "\n=== CLEARING PERMISSION CACHE ===\n";
    \Illuminate\Support\Facades\Artisan::call('permission:cache-reset');
    echo "✅ Cache de permisos limpiada.\n";

    echo "\n✅ ¡TODO LISTO! Ahora puedes crear usuarios y unidades sin problemas.\n";
    echo "⚠️  ELIMINA ESTE ARCHIVO (fix-unique.php) por seguridad.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
