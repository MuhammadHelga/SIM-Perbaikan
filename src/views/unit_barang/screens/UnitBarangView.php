<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Unit & Barang</title>
    <!-- Font Inter & Remixicon -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/unit.css">
    <link rel="stylesheet" href="/assets/css/navbar.css">
</head>
<body>
    <?php $activeMenu = 'unit_barang'; include BASE_PATH . '/components/shared/Navbar.php'; ?>
    <main class="container">
        <!-- Page Title -->
        <div class="page-title">
            <h1>Manajemen Data Unit/Ruang dan Barang</h1>
        </div>

        <!-- Metric Summary Cards -->
        <div class="cards-grid">
            <div class="card border-blue">
                <div class="card-body">
                    <span class="card-subtitle">TOTAL UNIT/RUANG</span>
                    <div class="card-value text-blue">35 <small>Unit/Ruang</small></div>
                </div>
                <div class="card-icon bg-blue">
                    <i class="ri-door-open-line"></i>
                </div>
            </div>

            <div class="card border-teal">
                <div class="card-body">
                    <span class="card-subtitle">TOTAL BARANG</span>
                    <div class="card-value text-teal">140 <small>Barang</small></div>
                </div>
                <div class="card-icon bg-teal">
                    <i class="ri-inbox-archive-line"></i>
                </div>
            </div>
        </div>

        <!-- Dual Column Table Layout -->
        <div class="tables-grid">
            
            <!-- Left Side: Daftar Unit/Ruangan -->
            <div class="table-card">
                <div class="table-header">
                    <div class="header-title">
                        <div class="icon-badge bg-blue"><i class="ri-door-open-line"></i></div>
                        <h3>Daftar Unit/Ruangan</h3>
                    </div>
                    <button class="btn btn-primary"><i class="ri-add-circle-line"></i> Tambah Unit</button>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="10%">No</th>
                                <th width="70%">Nama Ruang</th>
                                <th width="20%" class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Contoh perulangan baris data -->
                            <?php for ($i = 1; $i <= 9; $i++): ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td>Ihsan</td>
                                <td class="action-buttons">
                                    <a href="#" class="icon-action text-warning"><i class="ri-pencil-line"></i></a>
                                    <a href="#" class="icon-action text-danger"><i class="ri-delete-bin-line"></i></a>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Side: Daftar Barang -->
            <div class="table-card">
                <div class="table-header">
                    <div class="header-title">
                        <div class="icon-badge bg-teal"><i class="ri-inbox-archive-line"></i></div>
                        <h3>Daftar Barang</h3>
                    </div>
                    <button class="btn btn-primary"><i class="ri-add-circle-line"></i> Tambah Barang</button>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="10%">No</th>
                                <th width="70%">Nama Barang</th>
                                <th width="20%" class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Contoh perulangan baris data -->
                            <?php for ($i = 1; $i <= 9; $i++): ?>
                            <tr>
                                <td><?= $i ?></td>
                                <td>Komputer</td>
                                <td class="action-buttons">
                                    <a href="#" class="icon-action text-warning"><i class="ri-pencil-line"></i></a>
                                    <a href="#" class="icon-action text-danger"><i class="ri-delete-bin-line"></i></a>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

</body>
</html>