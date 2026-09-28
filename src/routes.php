<?php

/**
 * Pemuat rute: mengembalikan closure registrar.
 *
 * Dipanggil dari public/index.php:
 *   (require 'src/routes.php')($router, $conn, $basePath, $authService);
 *
 * Tiap file di src/routes/ juga mengembalikan registrar dengan tanda tangan yang sama,
 * sehingga dependensinya eksplisit dan terbaca alat analisis statis.
 */

return function (Router $router, mysqli $conn, string $basePath, AuthService $authService): void {
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
        /** @var callable $register */
        $register = require __DIR__ . '/routes/' . $routeFile . '.php';
        $register($router, $conn, $basePath, $authService);
    }
};
