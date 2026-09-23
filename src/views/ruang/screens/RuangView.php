<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Rekap Ruang</title>
    <!-- Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/ruang.css">
    <link rel="stylesheet" href="/assets/css/navbar.css">
</head>
<body>
    <?php $activeMenu = 'ruang'; include BASE_PATH . '/components/shared/Navbar.php'; ?>
    <main class="container">
        <!-- Page Title & Primary Action -->
        <div class="header-section">
            <div class="page-title">
                <h1>Total Barang Diperbaiki per Ruang/Urusan</h1>
                <p>Dihitung dari laporan dengan status Selesai di semua bulan periode tahun berjalan (Rekap Tahunan)</p>
            </div>
            <button class="btn btn-success"><i class="ri-printer-line"></i> Cetak Rekap Ruang</button>
        </div>

        <!-- Metric Cards Grid -->
        <div class="cards-grid">
            <div class="card border-blue">
                <div class="card-body">
                    <span class="card-subtitle">UNIT KERUSAKAN TERTINGGI</span>
                    <div class="card-value text-blue">POLI <span class="text-dark">123 Kasus</span></div>
                    <span class="card-footnote">Dengan akumulasi per tahun</span>
                </div>
                <div class="card-icon bg-blue">
                    <i class="ri-door-open-line"></i>
                </div>
            </div>

            <div class="card border-amber">
                <div class="card-body">
                    <span class="card-subtitle">BULAN PUNCAK KERUSAKAN</span>
                    <div class="card-value text-amber">Ags & Nov <span class="text-dark">23 Kasus</span></div>
                    <span class="card-footnote">Dengan akumulasi per tahun</span>
                </div>
                <div class="card-icon bg-amber">
                    <i class="ri-calendar-event-line"></i>
                </div>
            </div>

            <div class="card border-teal">
                <div class="card-body">
                    <span class="card-subtitle">TINGKAT KECEPATAN SERVIS</span>
                    <div class="card-value text-teal">96% <small class="text-success-sm">meningkat 2%</small></div>
                    <span class="card-footnote">Dengan akumulasi per tahun</span>
                </div>
                <div class="card-icon bg-teal">
                    <i class="ri-flashlight-line"></i>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-left">
                <select class="form-control select-year">
                    <option>Tahun 2026</option>
                    <option>Tahun 2025</option>
                </select>
                <div class="search-box">
                    <i class="ri-search-line"></i>
                    <input type="text" placeholder="Cari unit atau ruangan">
                </div>
            </div>
            <div class="filter-right">
                <label>Urut Berdasarkan:</label>
                <select class="form-control">
                    <option>Total Tertinggi</option>
                    <option>Total Terendah</option>
                </select>
            </div>
        </div>

        <!-- Main Data Table Container -->
        <div class="table-card">
            <!-- Table Sub-Header & Legend -->
            <div class="table-card-header">
                <div>
                    <h3 class="table-title">Distribusi Rekap Tahunan Alat dan Fasilitas Medis</h3>
                    <div class="legend-container">
                        <span class="legend-item"><span class="legend-box bg-normal"></span> Intensitas Normal (1–5)</span>
                        <span class="legend-item"><span class="legend-box bg-medium"></span> Intensitas Sedang (6–10)</span>
                        <span class="legend-item"><span class="legend-box bg-high"></span> Intensitas Tinggi (>10)</span>
                    </div>
                </div>
                <span class="badge-info">28 Unit/Ruangan Terdaftar</span>
            </div>

            <!-- Heatmap-style Matrix Table -->
            <div class="table-responsive">
                <table class="rekap-table">
                    <thead>
                        <tr>
                            <th class="text-left">Nama Unit</th>
                            <th>Januari</th>
                            <th>Februari</th>
                            <th>Maret</th>
                            <th>April</th>
                            <th>Mei</th>
                            <th>Juni</th>
                            <th>Juli</th>
                            <th>Agustus</th>
                            <th>September</th>
                            <th>Oktober</th>
                            <th>November</th>
                            <th>Desember</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-left font-bold">MJKN</td>
                            <td>0</td>
                            <td>0</td>
                            <td class="cell-medium">10</td>
                            <td>8</td>
                            <td>5</td>
                            <td class="cell-medium">3</td>
                            <td class="cell-high">15</td>
                            <td>7</td>
                            <td>3</td>
                            <td>6</td>
                            <td>5</td>
                            <td class="cell-high">17</td>
                        </tr>
                        <!-- Baris Tambahan (Dapat diulang menggunakan perulangan foreach PHP) -->
                        <tr>
                            <td class="text-left font-bold">MJKN</td>
                            <td>0</td>
                            <td>0</td>
                            <td class="cell-medium">10</td>
                            <td>8</td>
                            <td>5</td>
                            <td class="cell-medium">3</td>
                            <td class="cell-high">15</td>
                            <td>7</td>
                            <td>3</td>
                            <td>6</td>
                            <td>5</td>
                            <td class="cell-high">17</td>
                        </tr>
                        <tr>
                            <td class="text-left font-bold">MJKN</td>
                            <td>0</td>
                            <td>0</td>
                            <td class="cell-medium">10</td>
                            <td>8</td>
                            <td>5</td>
                            <td class="cell-medium">3</td>
                            <td class="cell-high">15</td>
                            <td>7</td>
                            <td>3</td>
                            <td>6</td>
                            <td>5</td>
                            <td class="cell-high">17</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="row-total">
                            <td class="text-left font-bold text-primary">TOTAL</td>
                            <td>0</td>
                            <td>0</td>
                            <td>10</td>
                            <td>8</td>
                            <td>5</td>
                            <td>3</td>
                            <td>15</td>
                            <td>7</td>
                            <td>3</td>
                            <td>6</td>
                            <td>5</td>
                            <td>17</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </main>

</body>
</html>