<?php
$laporanList  = $laporanList ?? [];
$stats        = $stats ?? ['total' => 0, 'pending' => 0, 'selesai' => 0];
$periode      = $periode ?? '';
$statusFilter = $statusFilter ?? '';
$search       = $search ?? '';

$namaBulan = [
    1=>'Januari',
    2=>'Februari',
    3=>'Maret',
    4=>'April',
    5=>'Mei',
    6=>'Juni',
    7=>'Juli',
    8=>'Agustus',
    9=>'September',
    10=>'Oktober',
    11=>'November',
    12=>'Desember'
];
$periodeTahun = $periode !== '' ? (int)substr($periode, 0, 4) : (int)date('Y');
$periodeBulan = $periode !== '' ? (int)substr($periode, 5, 2) : (int)date('n');
$periodeLabel = $namaBulan[$periodeBulan] . ' ' . $periodeTahun;
$periodeValue = sprintf('%04d-%02d', $periodeTahun, $periodeBulan);

// Tanggal dari DB berformat Y-m-d -> tampilkan d M Y
$fmtTanggal = function ($tgl) {
    if (empty($tgl)) {
        return '-';
    }
    $ts = strtotime((string)$tgl);
    return $ts ? date('d M Y', $ts) : (string)$tgl;
};
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Laporan Kegiatan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/laporan.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modal.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tambah-laporan-modal.css">
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
                <div class="filter-field month-picker" id="periodePicker">
                    <label for="periodeTrigger">Periode Bulan</label>
                    <div class="filter-input month-picker__input">
                        <span class="material-symbols-outlined">calendar_month</span>
                        <button type="button" class="month-picker__trigger" id="periodeTrigger">
                            <?= htmlspecialchars($periodeLabel, ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <span class="material-symbols-outlined month-picker__chevron">expand_more</span>
                    </div>
                    <input type="hidden" name="periode" id="periodeValue" value="<?= htmlspecialchars($periodeValue, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="month-picker__panel" id="periodePanel">
                        <div class="month-picker__year-nav">
                            <button type="button" class="month-picker__nav-btn" id="periodePrevYear" aria-label="Tahun sebelumnya">
                                <span class="material-symbols-outlined">chevron_left</span>
                            </button>
                            <span class="month-picker__year-label" id="periodeYearLabel"><?= htmlspecialchars($periodeTahun, ENT_QUOTES, 'UTF-8') ?></span>
                            <button type="button" class="month-picker__nav-btn" id="periodeNextYear" aria-label="Tahun berikutnya">
                                <span class="material-symbols-outlined">chevron_right</span>
                            </button>
                        </div>
                        <div class="month-picker__grid" id="periodeGrid"></div>
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
                            <td class="nowrap"><?= htmlspecialchars($fmtTanggal($row['tanggal'])) ?></td>
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
                                        <span class="pill-text">Dikirim: <?= htmlspecialchars($fmtTanggal($row['tgl_kirim'] ?? null)) ?></span>
                                        <button type="button" class="pill-btn pill-btn--green" onclick="terimaBarang(<?= (int)$row['id'] ?>)">Terima</button>
                                    </div>
                                <?php elseif ($row['kirim_status'] === 'diterima'): ?>
                                    <span class="pill-text">Diterima: <?= htmlspecialchars($fmtTanggal($row['tgl_terima'] ?? null)) ?></span>
                                <?php else: ?>
                                    <span class="pill-btn pill-btn--disabled">Kirim</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-aksi">
                                <div class="action-icons">
                                        <a href="<?= BASE_URL ?>/laporan/<?= (int)$row['id'] ?>" class="icon-btn icon-btn--view" title="Lihat detail">
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

    <div class="modal-overlay" id="modal-konfirmasi">
        <div class="confirm-card">
            <div class="confirm-card__icon">
                <span class="material-symbols-outlined" id="confirmIcon">local_shipping</span>
            </div>
            <h3 class="confirm-card__title" id="confirmTitle">Konfirmasi</h3>
            <p class="confirm-card__text" id="confirmText">Apakah kamu yakin?</p>
            <div class="confirm-card__actions">
                <button type="button" class="confirm-btn confirm-btn--cancel" onclick="closeModal('modal-konfirmasi')">Batal</button>
                <button type="button" class="confirm-btn confirm-btn--primary" id="confirmActionBtn">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>
</body>
<?php include __DIR__ . '/../../../../components/modals/TambahLaporanModal.php'; ?>
<script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/laporan.js"></script>
</html>
