<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Dashboard</title>
    <!-- Font Inter & Remixicon untuk Ikon -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>

    <!-- Top Navigation Header -->
    <header class="navbar">
        <div class="brand">
            <h2>SIM-Perbaikan</h2>
        </div>
        <!-- Di dalam file DashboardView.php & view lainnya -->
<nav class="nav-menu">
    <a href="/dashboard" class="nav-item"><i class="ri-dashboard-3-line"></i> Dashboard</a>
    
    <!-- Arahkan href ke URL /laporan -->
    <a href="/laporan" class="nav-item"><i class="ri-file-list-3-line"></i> Laporan Kegiatan</a>
    
    <a href="/ruang" class="nav-item"><i class="ri-door-open-line"></i> Rekap Ruang</a>
    <a href="/unit" class="nav-item"><i class="ri-shape-2-line"></i> Unit & Barang</a>
</nav>
        <div class="user-action">
            <a href="#" class="btn-logout"><i class="ri-logout-box-r-line"></i> Logout</a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="container">
        <!-- Page Title -->
        <div class="page-header">
            <h1>Dashboard dan Monitoring Perbaikan</h1>
            <p>Ringkasan performa pemeliharaan, tren kerusakan perangkat rumah sakit dan monitoring perbaikan real-time</p>
        </div>

        <!-- Metric Cards Grid -->
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

        <!-- Charts Layout Grid -->
        <div class="charts-grid">
            <!-- Left Chart Card -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Tren Laporan dan Penyelesaian (2026)</h3>
                    <p>Perbandingan jumlah tiket laporan masuk vs perbaikan terselesaikan</p>
                </div>
                <div class="chart-wrapper">
                    <canvas id="barChart"></canvas>
                </div>
            </div>

            <!-- Right Chart Card -->
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

    <!-- UI Scripts -->
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>