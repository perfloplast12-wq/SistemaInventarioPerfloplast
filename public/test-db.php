<?php
header('Content-Type: text/plain');

$host = 'perflo-db-farma.mysql.database.azure.com';
$db   = 'perflo_plast_db';
$user = 'perfloplast';
$pass = 'perfloplast123';
$port = 3306;
$charset = 'utf8mb4';

// Use the absolute path confirmed by diagnose.php
$ca_path = '/var/www/html/DigiCertGlobalRootG2.crt.pem';

echo "--- MySQL SSL Connection Diagnostic ---\n";
echo "Testing connection to $host...\n";
echo "Using Cert Path: $ca_path\n";
echo "Cert File Exists: " . (file_exists($ca_path) ? "YES" : "NO") . "\n";
echo "Cert File Readable: " . (is_readable($ca_path) ? "YES" : "NO") . "\n";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_SSL_CA       => $ca_path,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
];

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";

try {
     echo "Attempting PDO connection...\n";
     $pdo = new PDO($dsn, $user, $pass, $options);
     echo "SUCCESS: Connected to MySQL using SSL!\n";
     
     $stmt = $pdo->query("SELECT VERSION() as version");
     $row = $stmt->fetch();
     echo "MySQL Version: " . $row['version'] . "\n";
     
     $stmt = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'");
     $row = $stmt->fetch();
     echo "SSL Cipher in use: " . ($row['Value'] ?? 'None') . "\n";

} catch (\PDOException $e) {
     echo "ERROR: Could not connect to database.\n";
     echo "Exception Message: " . $e->getMessage() . "\n";
     echo "Error Code: " . $e->getCode() . "\n";
     
     echo "\n--- Troubleshooting Tips ---\n";
     if (strpos($e->getMessage(), 'handshake') !== false) {
         echo "- SSL Handshake failed. The certificate might not match the server's requirement.\n";
     }
     if (strpos($e->getMessage(), '2002') !== false) {
         echo "- Network or SSL initialization error. Check if 'Public Access' is allowed in Azure MySQL Network settings.\n";
     }
}
