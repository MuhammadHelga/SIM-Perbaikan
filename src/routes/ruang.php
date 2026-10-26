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
        $kecepatanServis = $ruangData['kecepatanServis'];

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

    // Export CSV rekap ruang: mengikuti tahun, pencarian, dan urutan yang aktif.
    $router->get('/ruang/export', function () use ($conn, $basePath) {
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

        $ruangData     = $ruangService->getData($tahun, $q, $sort);
        $rows          = $ruangData['rows'];
        $totalPerBulan = $ruangData['totalPerBulan'];
        $totalKeseluruhan = array_sum($totalPerBulan);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="rekap-ruang-' . $tahun . '.csv"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        // BOM agar Excel membaca UTF-8 dengan benar.
        fwrite($out, "\xEF\xBB\xBF");

        $bulanSingkat = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        $header = array_merge(['No', 'Nama Unit / Ruangan'], $bulanSingkat, ['Total']);
        fputcsv($out, array_map('csvCell', $header), ',', '"', '');

        foreach ($rows as $index => $row) {
            $line = [$index + 1, $row['nama'] ?? ''];
            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $line[] = (int) ($row['bulan'][$bulan] ?? 0);
            }
            $line[] = (int) ($row['total'] ?? 0);

            fputcsv($out, array_map('csvCell', $line), ',', '"', '');
        }

        $footer = ['', 'TOTAL'];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $footer[] = (int) ($totalPerBulan[$bulan] ?? 0);
        }
        $footer[] = $totalKeseluruhan;

        fputcsv($out, array_map('csvCell', $footer), ',', '"', '');

        fclose($out);
    });
};
