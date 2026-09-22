<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/navbar.css">
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
                    <div class="card-value">150 <small>KASUS</small></div>
                    <span class="card-badge text-success"><i class="ri-arrow-up-line"></i> +12 % vs bulan lalu</span>
                </div>
                <div class="card-icon bg-light-purple">
                    <i class="ri-sigma-line"></i>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="card-title">DALAM PENANGANAN</span>
                    <div class="card-value text-warning">15 <small>UNIT</small></div>
                    <span class="card-badge text-warning">• Butuh tindakan cepat</span>
                </div>
                <div class="card-icon bg-light-yellow">
                    <i class="ri-time-line"></i>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="card-title">SELESAI DITANGANI</span>
                    <div class="card-value text-success">15 <small>UNIT</small></div>
                    <span class="card-badge text-success">97% Solve Rate</span>
                </div>
                <div class="card-icon bg-light-green">
                    <i class="ri-checkbox-circle-line"></i>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <span class="card-title">KERUSAKAN KRITIS</span>
                    <div class="card-value text-danger">12 <small>UNIT</small></div>
                    <span class="card-badge text-danger">Prioritas tinggi perbaikan</span>
                </div>
                <div class="card-icon bg-light-red">
                    <i class="ri-error-warning-line"></i>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Tren Laporan dan Penyelesaian (2026)</h3>
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
                        <span class="text-muted">Sep 2026</span>
                    </div>
                    <p>Perangkat paling sering membutuhkan tindakan</p>
                </div>
                <div class="chart-wrapper doughnut-wrapper">
                    <canvas id="doughnutChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <script src="/assets/js/dashboard.js"></script>
</body>
</html>