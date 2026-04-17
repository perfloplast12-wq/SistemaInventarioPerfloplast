<?php
header('Content-Type: text/plain');

$host = 'perflo-db-farma.mysql.database.azure.com';
$db   = 'perflo_plast_db';
$user = getenv('DB_USERNAME') ?: 'admin_perflo';
$pass = getenv('DB_PASSWORD') ?: 'Perfloplast123.';
$port = 3306;
$ca_path = '/var/www/html/DigiCertGlobalRootG2.crt.pem';

echo "--- MySQL SSL Deep Diagnostic (v2.2 - RBAC Update 17-04-26) ---\n";
echo "Host: $host\n";
echo "Cert File: $ca_path (" . (file_exists($ca_path) ? "EXISTS" : "MISSING") . ")\n\n";

function test_conn($name, $options, $dsn, $user, $pass) {
    echo "TEST [$name]: ";
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        echo "SUCCESS!\n";
        $stmt = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'");
        $row = $stmt->fetch();
        echo "   -> Cipher: " . ($row['Value'] ?? 'None') . "\n\n";
    } catch (\Exception $e) {
        echo "FAILED\n";
        echo "   -> Error: " . $e->getMessage() . "\n\n";
    }
}

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4";

// Test 1: Explicit CA + Verify (Standard)
test_conn("A: Explicit CA + Verify", [
    PDO::MYSQL_ATTR_SSL_CA => $ca_path,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
], $dsn, $user, $pass);

// Test 2: No CA + Verify (System Store)
test_conn("B: System CA + Verify", [
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
], $dsn, $user, $pass);

// Test 3: No Verify (Insecure)
test_conn("C: SSL Enabled, NO Verify", [
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
], $dsn, $user, $pass);

echo "--- Step 4: Database Migrations ---\n";
if (isset($_GET['migrate'])) {
    set_time_limit(0);
    ini_set('output_buffering', 'off');
    ini_set('zlib.output_compression', false);
    while (ob_get_level()) ob_end_flush();
    ob_implicit_flush(true);

    echo "--- ULTRA-VERBOSE MIGRATION RUNNER ---\n";
    echo "Running migrations one by one...\n\n";
    
    try {
        $app = require_once __DIR__.'/../bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        
        // We run a loop to migrate step by step
        for ($i = 1; $i <= 100; $i++) {
            $status = $kernel->call('migrate', ['--force' => true, '--step' => 1]);
            $output = trim($kernel->output());
            
            if (strpos($output, 'Nothing to migrate') !== false) {
                echo "\nFINISH: All migrations completed!\n";
                break;
            }
            
            echo "Step $i: " . $output . "\n";
            echo str_pad("", 4096, " "); // Force browser to render
            usleep(100000); // 0.1s pause for visibility
        }

        echo "\n--- Running Seeders ---\n";
        echo "Running PermissionsSeeder...\n";
        $kernel->call('db:seed', ['--class' => 'PermissionsSeeder', '--force' => true]);
        echo $kernel->output() . "\n";

        echo "Running RolesSeeder...\n";
        $kernel->call('db:seed', ['--class' => 'RolesSeeder', '--force' => true]);
        echo $kernel->output() . "\n";
        
        
    } catch (\Exception $e) {
        echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "[ CLICK HERE TO RUN MIGRATIONS: https://perfloplast.azurewebsites.net/db-migrate.php?migrate=1 ]\n";
    
    echo "\n--- File System Audit ---\n";
    echo "Current Dir: " . __DIR__ . "\n";
    $base = realpath(__DIR__ . '/..');
    echo "Base Path: $base\n";
    
    $checks = [
        'vendor' => $base . '/vendor',
        'bootstrap/cache' => $base . '/bootstrap/cache',
        'storage' => $base . '/storage',
        'vendor/autoload.php' => $base . '/vendor/autoload.php'
    ];
    
    foreach ($checks as $name => $path) {
        $exists = file_exists($path) ? "EXISTS" : "MISSING";
        $perms = $exists === "EXISTS" && is_readable($path) ? "READABLE" : "NOT READABLE";
        echo " - $name: $exists ($perms) [$path]\n";
        if ($exists === "EXISTS" && is_dir($path)) {
            $files = scandir($path);
            echo "   (Items: " . count($files) . ")\n";
        }
    }

    echo "\n--- Current Tables in DB ---\n";
    try {
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($tables)) {
            echo "Database is EMPTY. Please click the migration link above.\n";
        } else {
            echo "Found " . count($tables) . " tables:\n";
            foreach($tables as $t) echo " - $t\n";
        }
    } catch (\Exception $e) {
        echo "Error listing tables: " . $e->getMessage() . "\n";
    }
}

echo "\n--- Troubleshooting Summary ---\n";
echo "1. If ALL fail: Check Azure Database Firewall ('Allow public access' must be ON).\n";
echo "2. If only C works: The certificate you are using does not match the server's certificate.\n";
echo "3. If B works: Good news! Azure already has the cert in the system store.\n";
