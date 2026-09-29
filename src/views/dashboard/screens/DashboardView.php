<?php
$dashboard = $dashboard ?? [
    'total' => 0, 'dalam' => 0, 'selesai' => 0, 'kritis' => 0,
    'solveRate' => 0, 'delta' => null, 'tahun' => (int) date('Y'), 'tahunLalu' => (int) date('Y') - 1,
    'monthly' => ['masuk' => array_fill(1, 12, 0), 'selesai' => array_fill(1, 12, 0)],
    'byBarang' => [],
    'monthlyByBarang' => [],
];

$tahunList = $tahunList ?? [$dashboard['tahun']];

$delta      = $dashboard['delta'];
$deltaText  = $delta === null
    ? 'Belum ada pembanding'
    : ($delta >= 0 ? '+' . $delta . ' % vs tahun lalu' : $delta . ' % vs tahun lalu');
$deltaClass = ($delta !== null && $delta < 0) ? 'text-danger' : 'text-purple';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Dashboard</title>
    <link rel="icon" href="<?= BASE_URL ?>/favicon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#00288e">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css?v=<?= filemtime(dirname(__DIR__, 4) . '/public/assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css?v=<?= filemtime(dirname(__DIR__, 4) . '/public/assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css">
</head>
<body>
    <?php $activeMenu = 'dashboard'; include BASE_PATH . '/components/shared/Navbar.php'; ?>

    <main class="container">
        <div class="page-header">
            <div>
                <h1>Dashboard dan Monitoring Perbaikan</h1>
                <p>Ringkasan performa pemeliharaan, tren kerusakan perangkat rumah sakit dan monitoring perbaikan real-time</p>
            </div>
            <form method="get" action="<?= BASE_URL ?>/dashboard" class="year-filter">
                <div class="year-stepper" data-years="<?= htmlspecialchars(implode(',', $tahunList), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" class="year-step-btn" id="tahunPrev" aria-label="Tahun sebelumnya">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </button>
                    <span class="year-step-label">Tahun <?= (int) $dashboard['tahun'] ?></span>
                    <button type="button" class="year-step-btn" id="tahunNext" aria-label="Tahun berikutnya">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                    <input type="hidden" name="tahun" id="tahunValue" value="<?= (int) $dashboard['tahun'] ?>">
                </div>
            </form>
        </div>

        <div class="cards-grid">
            <div class="card">
                <div class="card-body">
                    <span class="card-title">TOTAL LAPORAN <?= (int)$dashboard['tahun'] ?></span>
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
                    <p>Perbandingan jumlah laporan masuk, pending, proses, dan selesai per bulan</p>
                </div>
                <div class="chart-wrapper">
                    <canvas id="barChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="flex-between">
                        <h3>Distribusi Perangkat</h3>
                        <span class="text-muted">Tahun <?= (int)$dashboard['tahun'] ?></span>
                    </div>
                    <p>Perangkat paling sering membutuhkan tindakan</p>
                </div>
                <div class="chart-wrapper doughnut-wrapper">
                    <canvas id="doughnutChart"></canvas>
                </div>
            </div>
            <div class="comparison-charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Perbandingan Kerusakan per Bulan Tahun <?= (int)$dashboard['tahun'] ?></h3>
                        <p>Jumlah laporan kerusakan setiap barang per bulan</p>
                    </div>
                    <div class="chart-wrapper monthly-items-wrapper">
                        <canvas id="monthlyItemsChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Persentase Perbaikan per Bulan Tahun <?= (int)$dashboard['tahun'] ?></h3>
                        <p>Persentase laporan per bulan dari total laporan tahunan</p>
                    </div>
                    <div class="chart-wrapper monthly-pie-wrapper">
                        <canvas id="monthlyPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script id="dashboardDataJson" type="application/json"><?= json_encode([
        'monthly'         => $dashboard['monthly'],
        'byBarang'        => $dashboard['byBarang'],
        'monthlyByBarang' => $dashboard['monthlyByBarang'],
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) ?></script>
    <script src="<?= BASE_URL ?>/assets/js/dashboard.js?v=<?= filemtime(dirname(__DIR__, 4) . '/public/assets/js/dashboard.js') ?>"></script>
</body>
</html>