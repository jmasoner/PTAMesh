<?php
/**
 * PTAMesh Structure Verification Script
 * Run from plugin root: php verify-structure.php
 */

$expected = [
    'ptamesh.php',
    'includes/class-ptamesh-jobcart.php',
    'includes/class-ptamesh-pricing.php',
    'includes/class-ptamesh-admin.php',
    'templates/admin-receiving.php',
    'templates/admin-settings.php',
    'templates/export-jobcart-pdf.php',
    'templates/export-jobcart-csv.php',
];

$root = __DIR__;
echo "Checking PTAMesh plugin structure in: $root\n\n";

foreach ($expected as $path) {
    $full = $root . '/' . $path;
    if (file_exists($full)) {
        echo "[OK]   $path\n";
    } else {
        echo "[MISS] $path\n";
    }
}

echo "\nDone.\n";
