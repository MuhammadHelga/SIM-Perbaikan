<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Laporan Kegiatan</title>
    <!-- Font & Icon -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/laporan.css">
</head>
<body>

    <!-- Header Navigation -->
    <header class="navbar">
        <div class="brand">
            <h2>SIM-Perbaikan</h2>
        </div>
        <nav class="nav-menu">
            <a href="/dashboard" class="nav-item"><i class="ri-dashboard-3-line"></i> Dashboard</a>
            <a href="/laporan" class="nav-item active"><i class="ri-file-list-3-line"></i> Laporan Kegiatan</a>
            <a href="/ruang" class="nav-item"><i class="ri-door-open-line"></i> Rekap Ruang</a>
            <a href="/unit" class="nav-item"><i class="ri-shape-2-line"></i> Unit & Barang</a>
        </nav>
        <div class="user-action">
            <a href="/logout" class="btn-logout"><i class="ri-logout-box-r-line"></i> Logout</a>
        </div>
    </header>

    <main class="container">
        <!-- Top Section: Title & Summary Cards -->
        <div class="header-section">
            <div class="page-title">
                <h1>Daftar Laporan<br>Kegiatan & Kerusakan</h1>
            </div>
            <div class="summary-cards">
                <div class="mini-card border-blue">
                    <div class="card-icon icon-blue"><i class="ri-sigma-line"></i></div>
                    <div>
                        <span class="card-label">Total Laporan Bulan Ini</span>
                        <div class="card-count text-blue">132 <small>Unit</small></div>
                    </div>
                </div>
                <div class="mini-card border-red">
                    <div class="card-icon icon-red"><i class="ri-time-line"></i></div>
                    <div>
                        <span class="card-label">Pending/Ditunda</span>
                        <div class="card-count text-red">12 <small>Unit</small></div>
                    </div>
                </div>
                <div class="mini-card border-green">
                    <div class="card-icon icon-green"><i class="ri-checkbox-circle-line"></i></div>
                    <div>
                        <span class="card-label">Selesai Ditangani</span>
                        <div class="card-count text-green">120 <small>Unit</small></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Card: Filter & Data Table -->
        <div class="table-container-card">
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-inputs">
                    <div class="form-group">
                        <label>Periode Bulan</label>
                        <div class="input-icon">
                            <i class="ri-calendar-event-line"></i>
                            <select>
                                <option>September 2026</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select>
                            <option>Semua</option>
                            <option>Selesai</option>
                            <option>Pending</option>
                        </select>
                    </div>
                    <div class="form-group search-group">
                        <label>Cari</label>
                        <div class="input-icon">
                            <i class="ri-search-line"></i>
                            <input type="text" placeholder="Cari nama barang, unit...">
                        </div>
                    </div>
                </div>
                <div class="filter-actions">
    <button class="btn btn-success"><i class="ri-printer-line"></i> Cetak Rekap Laporan</button>
    <!-- Ubah button Tambah Laporan menjadi elemen <a> -->
    <a href="/laporan/tambah" class="btn btn-primary"><i class="ri-add-circle-line"></i> Tambah Laporan</a>
</div>
            </div>

            <!-- Table Section -->
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Urusan/Ruangan</th>
                            <th>Barang</th>
                            <th>Serial Number</th>
                            <th>Kerusakan</th>
                            <th>Uraian Kegiatan</th>
                            <th>Hasil</th>
                            <th>Tgl Kirim/Terima</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>19 Sep 2026</td>
                            <td>Poli</td>
                            <td>SIMRS</td>
                            <td>-</td>
                            <td>Lorem ipsum is simply text...</td>
                            <td>Lorem ipsum is simply text...</td>
                            <td><span class="badge badge-success">Selesai</span></td>
                            <td><button class="btn-sm btn-gray">Kirim</button></td>
                            <td class="action-buttons">
                                <a href="#" class="icon-action text-info"><i class="ri-search-eye-line"></i></a>
                                <a href="#" class="icon-action text-warning"><i class="ri-pencil-line"></i></a>
                                <a href="#" class="icon-action text-danger"><i class="ri-delete-bin-line"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>19 Sep 2026</td>
                            <td>Poli</td>
                            <td>SIMRS</td>
                            <td>-</td>
                            <td>Lorem ipsum is simply text...</td>
                            <td>Lorem ipsum is simply text...</td>
                            <td><span class="badge badge-warning">Pending</span></td>
                            <td>
                                <span class="date-text">19 Sep 2026</span>
                                <button class="btn-sm btn-teal">Terima</button>
                            </td>
                            <td class="action-buttons">
                                <a href="#" class="icon-action text-info"><i class="ri-search-eye-line"></i></a>
                                <a href="#" class="icon-action text-warning"><i class="ri-pencil-line"></i></a>
                                <a href="#" class="icon-action text-danger"><i class="ri-delete-bin-line"></i></a>
                            </td>
                        </tr>
                        <tr>
                            <td>19 Sep 2026</td>
                            <td>Poli</td>
                            <td>SIMRS</td>
                            <td>-</td>
                            <td>Lorem ipsum is simply text...</td>
                            <td>Lorem ipsum is simply text...</td>
                            <td><span class="badge badge-success">Selesai</span></td>
                            <td><span class="date-text">Diterima: 19 Sep 2026</span></td>
                            <td class="action-buttons">
                                <a href="#" class="icon-action text-info"><i class="ri-search-eye-line"></i></a>
                                <a href="#" class="icon-action text-warning"><i class="ri-pencil-line"></i></a>
                                <a href="#" class="icon-action text-danger"><i class="ri-delete-bin-line"></i></a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer / Pagination -->
            <div class="table-footer">
                <div class="entries-info">
                    Menampilkan 
                    <select>
                        <option>25</option>
                        <option>50</option>
                    </select> 
                    Laporan
                </div>
            </div>
        </div>
    </main>


</body>
</html>