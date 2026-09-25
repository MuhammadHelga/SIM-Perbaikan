<?php
$laporanList  = $laporanList ?? [];
$stats        = $stats ?? ['total' => 0, 'pending' => 0, 'selesai' => 0];
$periode      = $periode ?? '';
$statusFilter = $statusFilter ?? '';
$search       = $search ?? '';
$perPage      = $perPage ?? 25;

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

// Chip "Filter Aktif" (masing-masing bisa dihapus lewat link)
$chipUrl = function (array $overrides) use ($periode, $statusFilter, $search, $perPage) {
    $params = [
        'periode'  => $periode,
        'status'   => $statusFilter,
        'search'   => $search,
        'per_page' => $perPage,
    ];
    foreach ($overrides as $k => $v) {
        $params[$k] = $v;
    }

    $query = http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== null));

    return BASE_URL . '/laporan' . ($query !== '' ? '?' . $query : '');
};

$activeChips = [];
if ($periode !== '') {
    $activeChips[] = ['label' => 'Periode: ' . $periodeLabel, 'url' => $chipUrl(['periode' => ''])];
}
if ($statusFilter !== '') {
    $activeChips[] = ['label' => 'Status: ' . $statusFilter, 'url' => $chipUrl(['status' => ''])];
}
if ($search !== '') {
    $activeChips[] = ['label' => 'Cari: ' . $search, 'url' => $chipUrl(['search' => ''])];
}
?>

<!DOCTYPE html>
<html lang="id" data-theme="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light' ?>">
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css">
</head>
<body>
    <?php $activeMenu = 'laporan'; include BASE_PATH . '/components/shared/Navbar.php'; ?>

    <div class="print-only print-head">
        <h1>SIM-Perbaikan &mdash; RS Al-Huda</h1>
        <h2>Rekap Laporan Kegiatan &amp; Kerusakan</h2>
        <p>Periode: <?= htmlspecialchars($periodeLabel, ENT_QUOTES, 'UTF-8') ?> &middot; Dicetak: <?= date('d M Y') ?></p>
        <p>Total: <?= (int)$stats['total'] ?> &middot; Pending/Proses: <?= (int)$stats['pending'] ?> &middot; Selesai: <?= (int)$stats['selesai'] ?></p>
    </div>
    
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
                <input type="hidden" name="per_page" id="perPageValue" value="<?= (int)$perPage ?>">
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
                    <label for="filterStatus">Status</label>
                    <div class="filter-input filter-input--select">
                        <select name="status" id="filterStatus">
                            <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>Semua</option>
                            <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Proses" <?= $statusFilter === 'Proses' ? 'selected' : '' ?>>Proses</option>
                            <option value="Selesai" <?= $statusFilter === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
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
                <span id="activeChips">
                    <?php if (!$activeChips): ?>
                        <span class="chip chip--empty">Tidak ada</span>
                    <?php else: ?>
                        <?php foreach ($activeChips as $chip): ?>
                            <a class="chip" href="<?= htmlspecialchars($chip['url'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($chip['label'], ENT_QUOTES, 'UTF-8') ?>
                                <span class="chip__x" aria-hidden="true">&times;</span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </span>
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
                                <?php
                                    $statusPenanganan = $row['status_penanganan'] ?? 'Pending';
                                    $badgeClass = match ($statusPenanganan) {
                                        'Selesai' => 'badge--green',
                                        'Proses'  => 'badge--orange',
                                        default   => 'badge--red',
                                    };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusPenanganan) ?></span>
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
                                        <button type="button" class="icon-btn icon-btn--view" title="Lihat detail" onclick="openDetailModal(<?= (int)$row['id'] ?>)">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </button>
                                    <button type="button" class="icon-btn icon-btn--edit" title="Edit" onclick="openEditModal(<?= (int)$row['id'] ?>)">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                                    <button type="button" class="icon-btn icon-btn--delete" title="Hapus" onclick="confirmDelete(<?= (int)$row['id'] ?>)">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                    <?php endif; ?>
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
                    <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100</option>
                    <option value="200" <?= $perPage === 200 ? 'selected' : '' ?>>200</option>
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
<script id="laporanDataJson" type="application/json"><?= json_encode($laporanList, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) ?></script>
<script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/laporan.js"></script>
</html>
