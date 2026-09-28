<?php

/**
 * Pemuat rute. Di-require dari public/index.php.
 *
 * Setiap file di src/routes/ memakai variabel dari bootstrap:
 * $router, $conn, $basePath, $authService.
 */

$routeFiles = [
    'auth',
    'dashboard',
    'laporan',
    'ruang',
    'unit',
    'jaringan',
    'subnet',
];

foreach ($routeFiles as $routeFile) {
    require __DIR__ . '/routes/' . $routeFile . '.php';
}
