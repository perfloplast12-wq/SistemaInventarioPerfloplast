<?php
header('Content-Type: text/plain');
$basePath = dirname(__DIR__);
echo "Base Path (Calculated): " . $basePath . "\n";
echo "Cert Path: " . $basePath . DIRECTORY_SEPARATOR . 'DigiCertGlobalRootG2.crt.pem' . "\n";
echo "Cert File Exists: " . (file_exists($basePath . DIRECTORY_SEPARATOR . 'DigiCertGlobalRootG2.crt.pem') ? 'YES' : 'NO') . "\n";
echo "Cert File Readable: " . (is_readable($basePath . DIRECTORY_SEPARATOR . 'DigiCertGlobalRootG2.crt.pem') ? 'YES' : 'NO') . "\n";
echo "Current User: " . get_current_user() . "\n";
echo "Directory Listing of Base Path:\n";
print_r(scandir($basePath));
