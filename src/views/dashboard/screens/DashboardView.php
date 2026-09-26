<?php
$dashboard = $dashboard ?? [
    'total' => 0, 'dalam' => 0, 'selesai' => 0, 'kritis' => 0,
    'solveRate' => 0, 'delta' => null, 'tahun' => (int) date('Y'), 'bulan' => (int) date('n'),
    'monthly' => ['masuk' => array_fill(1, 12, 0), 'selesai' => array_fill(1, 12, 0)],
    'byBarang' => [],
];

$delta      = $dashboard['delta'];
$deltaText  = $delta === null
    ? 'Belum ada pembanding'
    : ($delta >= 0 ? '+' . $delta . ' % vs bulan lalu' : $delta . ' % vs bulan lalu');
$deltaClass = ($delta !== null && $delta < 0) ? 'text-danger' : 'text-purple';

$namaBulanSingkat = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Ags',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
$periodeLabel = $namaBulanSingkat[$dashboard['bulan']] . ' ' . $dashboard['tahun'];
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Dashboard</title>
    <link rel="icon" href="<?= BASE_URL ?>/favicon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#00288e">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css">
</head>
<body>
    <?php $activeMenu = 'dashboard'; include BASE_PATH . '/components/shared/Navbar.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1>Dashboard dan Monitoring Perbaikan</h1>
            <p>Ringkasan performa pemeliharaan, tren kerusakan perangkat rumah sakit dan monitoring perbaikan real-time</p>
        </div>

        <div class="cards-grid">
            <div class="card">
                <div class="card-body">
                    <span class="card-title">TOTAL LAPORAN</span>
                    <div class="card-value text-purple"><?= (int)$dashboard['total'] ?> <small>KASUS</small></div>
                    <span class="card-badge <?= $deltaClass ?>"><?= htmlspecialchars($deltaText) ?></span>
                </div>
                <div class="card-icon bg-light-purple">
                    <span class="material-symbols-outlined">functions</span>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="card-title">DALAM PENANGANAN</span>
                    <div class="card-value text-warning"><?= (int)$dashboard['dalam'] ?> <small>UNIT</small></div>
                    <span class="card-badge text-warning">Butuh tindakan cepat</span>
                </div>
                <div class="card-icon bg-light-yellow">
                    <span class="material-symbols-outlined">schedule</span>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="card-title">SELESAI DITANGANI</span>
                    <div class="card-value text-success"><?= (int)$dashboard['selesai'] ?> <small>UNIT</small></div>
                    <span class="card-badge text-success"><?= (int)$dashboard['solveRate'] ?>% Solve Rate</span>
                </div>
                <div class="card-icon bg-light-green">
                    <span class="material-symbols-outlined">check_circle</span>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="card-title">KERUSAKAN KRITIS (BELUM SELESAI)</span>
                    <div class="card-value text-danger"><?= (int)$dashboard['kritis'] ?> <small>UNIT</small></div>
                    <span class="card-badge text-danger">Prioritas tinggi perbaikan</span>
                </div>
                <div class="card-icon bg-light-red">
                    <span class="material-symbols-outlined">error</span>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Tren Laporan dan Penyelesaian (<?= (int)$dashboard['tahun'] ?>)</h3>
                    <p>Perbandingan jumlah tiket laporan masuk vs perbaikan terselesaikan</p>
                </div>
                <div class="chart-wrapper">
                    <canvas id="barChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="flex-between">
                        <h3>Distribusi Kategori Perbaikan</h3>
                        <span class="text-muted"><?= htmlspecialchars($periodeLabel) ?></span>
                    </div>
                    <p>Perangkat paling sering membutuhkan tindakan</p>
                </div>
                <div class="chart-wrapper doughnut-wrapper">
                    <canvas id="doughnutChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <script id="dashboardDataJson" type="application/json"><?= json_encode([
        'monthly'  => $dashboard['monthly'],
        'byBarang' => $dashboard['byBarang'],
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) ?></script>
    <script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>