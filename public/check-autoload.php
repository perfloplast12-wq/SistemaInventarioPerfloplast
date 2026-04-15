<?php
// Temporary diagnostic - DELETE after verifying deployment
// Access via: perfloplast.azurewebsites.net/check-autoload.php

echo "<h2>Autoloader Diagnostic</h2>";
echo "<pre>";

// Check if the class exists
echo "1. class_exists('App\\Models\\AuditLog'): " . (class_exists('App\Models\AuditLog') ? 'YES' : 'NO') . "\n\n";

// Check the file on disk
$file = __DIR__ . '/../app/Models/AuditLog.php';
echo "2. File exists at app/Models/AuditLog.php: " . (file_exists($file) ? 'YES' : 'NO') . "\n";
if (file_exists($file)) {
    echo "   File size: " . filesize($file) . " bytes\n";
    echo "   First line: " . trim(fgets(fopen($file, 'r'))) . "\n";
}
echo "\n";

// List all files in Models directory
echo "3. Files in app/Models/:\n";
$modelsDir = __DIR__ . '/../app/Models/';
if (is_dir($modelsDir)) {
    $files = scandir($modelsDir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        echo "   - $f" . (is_dir($modelsDir . $f) ? ' [DIR]' : '') . "\n";
    }
}
echo "\n";

// Check classmap
echo "4. Classmap entry for AuditLog:\n";
$classmap = __DIR__ . '/../vendor/composer/autoload_classmap.php';
if (file_exists($classmap)) {
    $map = require $classmap;
    foreach ($map as $class => $path) {
        if (stripos($class, 'AuditLog') !== false) {
            echo "   $class => $path\n";
            echo "   File at mapped path exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
        }
    }
} else {
    echo "   Classmap file NOT found!\n";
}
echo "\n";

// Check working directory
echo "5. Working directory: " . getcwd() . "\n";
echo "6. Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "7. Script filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";

echo "</pre>";
