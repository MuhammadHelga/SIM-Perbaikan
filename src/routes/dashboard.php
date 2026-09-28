<?php

/**
 * Rute beranda & dashboard.
 * Variabel dari bootstrap: $router, $conn, $basePath.
 */

$router->any('/', function () use ($basePath) {
    if (empty($_SESSION['is_logged_in'])) {
        redirect($basePath . '/login');
    }

    redirect($basePath . '/dashboard');
});

$router->any('/dashboard', function () use ($conn, $basePath) {
    requireLogin($basePath);

    require_once __DIR__ . '/../services/DashboardService.php';
    $dashboard = (new DashboardService($conn))->getData((int) date('Y'));

    require __DIR__ . '/../views/dashboard/screens/DashboardView.php';
});
