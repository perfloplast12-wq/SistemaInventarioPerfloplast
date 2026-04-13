<?php
header('Content-Type: text/plain');

$host = 'perflo-db-farma.mysql.database.azure.com';
$db   = 'perflo_plast_db';
$user = getenv('DB_USERNAME') ?: 'admin_perflo';
$pass = getenv('DB_PASSWORD') ?: 'Perfloplast123.';
$port = 3306;
$ca_path = '/var/www/html/DigiCertGlobalRootG2.crt.pem';

echo "--- MySQL SSL Deep Diagnostic (v2.1) ---\n";
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

echo "--- Troubleshooting Summary ---\n";
echo "1. If ALL fail: Check Azure Database Firewall ('Allow public access' must be ON).\n";
echo "2. If only C works: The certificate you are using does not match the server's certificate.\n";
echo "3. If B works: Good news! Azure already has the cert in the system store.\n";
