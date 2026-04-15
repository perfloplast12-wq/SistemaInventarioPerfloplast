<?php
// Temporary diagnostic - DELETE after verifying deployment
// Access via: perfloplast.azurewebsites.net/diagnose-full.php

require __DIR__ . '/../vendor/autoload.php';

echo "<h2>Perflo-Plast Production Diagnostic</h2>";
echo "<pre style='background:#111;color:#0f0;padding:20px;font-size:14px;'>";

// 1. AuditLog class check
echo "=== 1. AUTOLOADER CHECK ===\n";
echo "class_exists('App\\Models\\AuditLog'): " . (class_exists('App\Models\AuditLog') ? '✅ YES' : '❌ NO') . "\n";
echo "class_exists('App\\Models\\User'):     " . (class_exists('App\Models\User') ? '✅ YES' : '❌ NO') . "\n";
echo "class_exists('App\\Models\\Product'):  " . (class_exists('App\Models\Product') ? '✅ YES' : '❌ NO') . "\n";
echo "class_exists('App\\Models\\Sale'):     " . (class_exists('App\Models\Sale') ? '✅ YES' : '❌ NO') . "\n";
echo "class_exists('App\\Services\\AuditLogger'): " . (class_exists('App\Services\AuditLogger') ? '✅ YES' : '❌ NO') . "\n\n";

// 2. File on disk
echo "=== 2. FILES ON DISK ===\n";
$modelsDir = __DIR__ . '/../app/Models/';
if (is_dir($modelsDir)) {
    $files = scandir($modelsDir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $fullPath = $modelsDir . $f;
        $icon = is_dir($fullPath) ? '📁' : '📄';
        echo "   $icon $f\n";
    }
} else {
    echo "   ❌ Models directory NOT found at: $modelsDir\n";
}
echo "\n";

// 3. Classmap check
echo "=== 3. CLASSMAP ENTRIES (AuditLog) ===\n";
$classmapFile = __DIR__ . '/../vendor/composer/autoload_classmap.php';
if (file_exists($classmapFile)) {
    $map = require $classmapFile;
    $found = false;
    foreach ($map as $class => $path) {
        if (stripos($class, 'Audit') !== false) {
            $exists = file_exists($path) ? '✅' : '❌';
            echo "   $exists $class => $path\n";
            $found = true;
        }
    }
    if (!$found) echo "   ❌ No AuditLog entries in classmap!\n";
    echo "   Total classes in classmap: " . count($map) . "\n";
} else {
    echo "   ❌ Classmap file NOT found!\n";
}
echo "\n";

// 4. Database connection and tables
echo "=== 4. DATABASE TABLES ===\n";
try {
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $db   = getenv('DB_DATABASE') ?: 'inventario_fabrica';
    $user = getenv('DB_USERNAME') ?: 'root';
    $pass = getenv('DB_PASSWORD') ?: '';
    $ssl  = getenv('MYSQL_ATTR_SSL_CA') ?: '';

    echo "   Host: $host:$port\n";
    echo "   Database: $db\n";
    echo "   User: $user\n";
    echo "   SSL CA: " . ($ssl ? $ssl : 'none') . "\n\n";

    $dsn = "mysql:host=$host;port=$port;dbname=$db";
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    if ($ssl && file_exists($ssl)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $ssl;
    }

    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "   ✅ Connected successfully!\n\n";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Tables found: " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "   📋 $table\n";
    }

    // Check migrations table
    echo "\n=== 5. MIGRATIONS STATUS ===\n";
    try {
        $stmt = $pdo->query("SELECT migration FROM migrations ORDER BY id");
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   Migrations run: " . count($migrations) . "\n";
        foreach ($migrations as $m) {
            echo "   ✅ $m\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Could not read migrations table: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "   ❌ Connection failed: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Environment info
echo "=== 6. ENVIRONMENT ===\n";
echo "   PHP: " . PHP_VERSION . "\n";
echo "   CWD: " . getcwd() . "\n";
echo "   Doc Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n";
echo "   Script: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'unknown') . "\n";

echo "</pre>";
