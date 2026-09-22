<?php
$laporanList  = $laporanList ?? [];
$stats        = $stats ?? ['total' => 0, 'pending' => 0, 'selesai' => 0];
$periode      = $periode ?? '';
$statusFilter = $statusFilter ?? '';
$search       = $search ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Laporan Kegiatan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/navbar.css">
    <link rel="stylesheet" href="/assets/css/laporan.css">
    <link rel="stylesheet" href="/assets/css/modal.css">
    <link rel="stylesheet" href="/assets/css/tambah-laporan-modal.css">
</head>
<body>
    <?php $activeMenu = 'laporan'; include BASE_PATH . '/components/shared/Navbar.php'; ?>
    
    <main class="page-wrap">
        <div class="page-head">
            <h1 class="page-title">Daftar Laporan<br>Kegiatan &amp; Kerusakan</h1>

            <div class="stat-row">
                <div class="stat-box stat-box--purple">
                    <span class="stat-box__icon material-symbols-outlined">functions</span>
                    <div>
                        <p class="stat-box__label">Total Laporan Bulan Ini</p>
                        <p class="stat-box__value"><span class="value-blue"><?= (int)$stats['total'] ?></span> Unit</p>
                    </div>
                </div>
                <div class="stat-box stat-box--red">
                    <span class="stat-box__icon material-symbols-outlined">schedule</span>
                    <div>
                        <p class="stat-box__label">Pending/Ditunda</p>
                        <p class="stat-box__value"><span class="value-red"><?= (int)$stats['pending'] ?></span> Unit</p>
                    </div>
                </div>
                <div class="stat-box stat-box--green">
                    <span class="stat-box__icon material-symbols-outlined">check_circle</span>
                    <div>
                        <p class="stat-box__label">Selesai Ditangani</p>
                        <p class="stat-box__value"><span class="value-green"><?= (int)$stats['selesai'] ?></span> Unit</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-card">
            <form method="get" class="filter-row" id="filterForm">
                <div class="filter-field">
                    <label for="periode">Periode Bulan</label>
                    <div class="filter-input">
                        <span class="material-symbols-outlined">calendar_month</span>
                        <select name="periode" id="periode">
                            <option value="2026-09">September 2026</option>
                            <option value="2026-08">Agustus 2026</option>
                            <option value="2026-07">Juli 2026</option>
                        </select>
                    </div>
                </div>

                <div class="filter-field">
                    <label for="status">Status</label>
                    <div class="filter-input">
                        <select name="status" id="status">
                            <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>Semua</option>
                            <option value="selesai" <?= $statusFilter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>
                </div>

                <div class="filter-field filter-field--grow">
                    <label for="search">Cari</label>
                    <div class="filter-input">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" name="search" id="search" placeholder="Cari nama barang, unit..."
                            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="button" class="btn btn-print" id="btnCetak">
                        <span class="material-symbols-outlined">print</span>
                        Cetak Rekap Laporan
                    </button>
                    <button type="button" class="btn btn-add" onclick="openTambahModal()">
                        <span class="material-symbols-outlined">add</span>
                        Tambah Laporan
                    </button>
                </div>
            </form>

            <div class="filter-active">
                <span class="filter-active__label">Filter Aktif:</span>
                <span id="activeChips"></span>
            </div>
        </div>

        <div class="table-card">
            <div class="table-scroll">
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
                            <th class="col-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$laporanList): ?>
                        <tr><td colspan="9" class="empty-row">Belum ada laporan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($laporanList as $row): ?>
                        <tr>
                            <td class="nowrap"><?= htmlspecialchars($row['tanggal']) ?></td>
                            <td><?= htmlspecialchars($row['urusan']) ?></td>
                            <td><?= htmlspecialchars($row['barang']) ?></td>
                            <td><?= htmlspecialchars($row['serial_number'] ?? '-') ?></td>
                            <td class="truncate" title="<?= htmlspecialchars($row['kerusakan']) ?>"><?= htmlspecialchars($row['kerusakan']) ?></td>
                            <td class="truncate" title="<?= htmlspecialchars($row['uraian']) ?>"><?= htmlspecialchars($row['uraian']) ?></td>
                            <td>
                                <?php if ($row['hasil'] === 'selesai'): ?>
                                    <span class="badge badge--green">Selesai</span>
                                <?php else: ?>
                                    <span class="badge badge--red">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="nowrap">
                                <?php if ($row['kirim_status'] === 'belum'): ?>
                                    <button type="button" class="pill-btn pill-btn--navy" onclick="kirimBarang(<?= (int)$row['id'] ?>)">Kirim</button>
                                <?php elseif ($row['kirim_status'] === 'dikirim'): ?>
                                    <div class="pill-stack">
                                        <span class="pill-text"><?= htmlspecialchars($row['tgl_kirim']) ?></span>
                                        <button type="button" class="pill-btn pill-btn--green" onclick="terimaBarang(<?= (int)$row['id'] ?>)">Terima</button>
                                    </div>
                                <?php elseif ($row['kirim_status'] === 'diterima'): ?>
                                    <span class="pill-text">Diterima: <?= htmlspecialchars($row['tgl_terima']) ?></span>
                                <?php else: ?>
                                    <span class="pill-btn pill-btn--disabled">Kirim</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-aksi">
                                <div class="action-icons">
                                    <a href="/laporan/<?= (int)$row['id'] ?>" class="icon-btn icon-btn--view" title="Lihat detail">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </a>
                                    <button type="button" class="icon-btn icon-btn--edit" title="Edit" onclick="openEditModal(<?= (int)$row['id'] ?>)">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button type="button" class="icon-btn icon-btn--delete" title="Hapus" onclick="confirmDelete(<?= (int)$row['id'] ?>)">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span>Menampilkan</span>
                <select id="perPage">
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>Laporan</span>
            </div>
        </div>
    </main>
    
</body>
<?php include __DIR__ . '/../../../../components/modals/TambahLaporanModal.php'; ?>
<script src="/assets/js/laporan.js"></script>
</html>