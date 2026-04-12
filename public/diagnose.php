<?php
header('Content-Type: text/plain');
echo "Base Path: " . base_path() . "\n";
echo "Cert Path: " . base_path('DigiCertGlobalRootG2.crt.pem') . "\n";
echo "Cert File Exists: " . (file_exists(base_path('DigiCertGlobalRootG2.crt.pem')) ? 'YES' : 'NO') . "\n";
echo "Cert File Readable: " . (is_readable(base_path('DigiCertGlobalRootG2.crt.pem')) ? 'YES' : 'NO') . "\n";
echo "Real Path: " . realpath(base_path('DigiCertGlobalRootG2.crt.pem')) . "\n";
echo "Current User: " . get_current_user() . " (" . posix_getpwuid(posix_geteuid())['name'] . ")\n";
echo "Directory Listing of Base Path:\n";
print_r(scandir(base_path()));
