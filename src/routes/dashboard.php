<?php

/**
 * Rute beranda & dashboard.
 */

return function (Router $router, mysqli $conn, string $basePath, AuthService $authService): void {
    $router->any('/', function () use ($basePath) {
        if (empty($_SESSION['is_logged_in'])) {
            redirect($basePath . '/login');
        }

        redirect($basePath . '/dashboard');
    });

    $router->any('/dashboard', function () use ($conn, $basePath) {
        requireLogin($basePath);

        require_once __DIR__ . '/../services/DashboardService.php';
        $service = new DashboardService($conn);

        $tahunList = $service->getTahunTersedia();
        $tahun     = (int) ($_GET['tahun'] ?? date('Y'));

        if (!in_array($tahun, $tahunList, true)) {
            $tahun = $tahunList[0] ?? (int) date('Y');
        }

        $dashboard = $service->getData($tahun);

        require __DIR__ . '/../views/dashboard/screens/DashboardView.php';
    });

    // Cetak dashboard: ringkasan KPI, tabel rekap bulanan, grafik & tabel distribusi.
    $router->any('/dashboard/cetak', function () use ($conn, $basePath) {
        requireLogin($basePath);

        require_once __DIR__ . '/../services/DashboardService.php';
        $service = new DashboardService($conn);

        $tahunList = $service->getTahunTersedia();
        $tahun     = (int) ($_GET['tahun'] ?? date('Y'));

        if (!in_array($tahun, $tahunList, true)) {
            $tahun = $tahunList[0] ?? (int) date('Y');
        }

        $dashboard = $service->getData($tahun);

        require __DIR__ . '/../views/dashboard/screens/DashboardCetakView.php';
    });
};
