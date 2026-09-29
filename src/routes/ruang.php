<?php

/**
 * Rute rekap ruang.
 */

return function (Router $router, mysqli $conn, string $basePath, AuthService $authService): void {
    $router->any('/ruang', function () use ($conn, $basePath) {
        requireLogin($basePath);

        require_once __DIR__ . '/../services/RuangService.php';
        $ruangService = new RuangService($conn);

        $sort      = ($_GET['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $q         = trim($_GET['q'] ?? '');
        $tahunList = $ruangService->getTahunTersedia();
        $tahun     = (int) ($_GET['tahun'] ?? date('Y'));

        if (!in_array($tahun, $tahunList, true)) {
            $tahun = $tahunList[0] ?? (int) date('Y');
        }

        $ruangData = $ruangService->getData($tahun, $q, $sort);

        $rows          = $ruangData['rows'];
        $totalPerBulan = $ruangData['totalPerBulan'];
        $topUnit       = $ruangData['topUnit'];
        $peakMonths    = $ruangData['peakMonths'];
        $peakTotal     = $ruangData['peakTotal'];
        $jumlahRuangan = $ruangData['jumlahRuangan'];

        require __DIR__ . '/../views/ruang/screens/RuangView.php';
    });

    // Cetak rekap ruang: tabel tunggal (header berulang per halaman), tanpa paginasi.
    $router->any('/ruang/cetak', function () use ($conn, $basePath) {
        requireLogin($basePath);

        require_once __DIR__ . '/../services/RuangService.php';
        $ruangService = new RuangService($conn);

        $sort      = ($_GET['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $q         = trim($_GET['q'] ?? '');
        $tahunList = $ruangService->getTahunTersedia();
        $tahun     = (int) ($_GET['tahun'] ?? date('Y'));

        if (!in_array($tahun, $tahunList, true)) {
            $tahun = $tahunList[0] ?? (int) date('Y');
        }

        $ruangData = $ruangService->getData($tahun, $q, $sort);

        $tahun         = (int) $ruangData['tahun'];
        $rows          = $ruangData['rows'];
        $totalPerBulan = $ruangData['totalPerBulan'];
        $topUnit       = $ruangData['topUnit'];
        $peakMonths    = $ruangData['peakMonths'];
        $peakTotal     = $ruangData['peakTotal'];

        require __DIR__ . '/../views/ruang/screens/RuangCetakView.php';
    });
};
