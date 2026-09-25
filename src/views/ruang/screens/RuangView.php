<?php
$tahun         = $tahun ?? (int) date('Y');
$q             = $q ?? '';
$sort          = $sort ?? 'desc';
$tahunList     = $tahunList ?? [(int) date('Y')];
$rows          = $rows ?? [];
$totalPerBulan = $totalPerBulan ?? array_fill(1, 12, 0);
$topUnit       = $topUnit ?? null;
$peakMonths    = $peakMonths ?? [];
$peakTotal     = $peakTotal ?? 0;
$jumlahRuangan = $jumlahRuangan ?? 0;

$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

$topUnitNama  = $topUnit['nama'] ?? '—';
$topUnitTotal = (int) ($topUnit['total'] ?? 0);
$peakLabel    = $peakMonths
    ? implode(' & ', array_map(fn($m) => mb_substr($m, 0, 3), $peakMonths))
    : '—';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Rekap Ruang</title>
    <!-- Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/ruang.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css">
</head>
<body>
    <?php $activeMenu = 'ruang'; include BASE_PATH . '/components/shared/Navbar.php'; ?>

    <div class="print-only print-head">
        <h1>SIM-Perbaikan &mdash; RS Al-Huda</h1>
        <h2>Total Barang Diperbaiki per Ruang/Urusan &mdash; Tahun <?= (int)$tahun ?></h2>
        <p>Dicetak: <?= date('d M Y') ?></p>
        <p>Keterangan: 1&ndash;5 Normal &middot; 6&ndash;10 Sedang &middot; &gt;10 Tinggi</p>
    </div>
    <main class="container">
        <!-- Page Title & Primary Action -->
        <div class="header-section">
            <div class="page-title">
                <h1>Total Barang Diperbaiki per Ruang/Urusan</h1>
                <p>Dihitung dari laporan dengan status Selesai di semua bulan periode tahun berjalan (Rekap Tahunan)</p>
            </div>
            <button class="btn btn-success" onclick="window.print()"><span class="material-symbols-outlined">print</span> Cetak Rekap Ruang</button>
        </div>

        <!-- Metric Cards Grid -->
        <div class="cards-grid">
            <div class="card border-blue">
                <div class="card-body">
                    <span class="card-subtitle">UNIT KERUSAKAN TERTINGGI</span>
                    <div class="card-value text-blue"><?= htmlspecialchars($topUnitNama) ?> <span class="text-dark"><?= $topUnitTotal ?> Kasus</span></div>
                    <span class="card-footnote">Dengan akumulasi per tahun</span>
                </div>
                <div class="card-icon bg-blue">
                    <span class="material-symbols-outlined">meeting_room</span>
                </div>
            </div>

            <div class="card border-amber">
                <div class="card-body">
                    <span class="card-subtitle">BULAN PUNCAK KERUSAKAN</span>
                    <div class="card-value text-amber"><?= htmlspecialchars($peakLabel) ?> <span class="text-dark"><?= (int)$peakTotal ?> Kasus</span></div>
                    <span class="card-footnote">Dengan akumulasi per tahun</span>
                </div>
                <div class="card-icon bg-amber">
                    <span class="material-symbols-outlined">calendar_month</span>
                </div>
            </div>

            <div class="card border-teal">
                <div class="card-body">
                    <span class="card-subtitle">TINGKAT KECEPATAN SERVIS</span>
                    <div class="card-value text-teal">—</div>
                    <span class="card-footnote">Belum dihitung</span>
                </div>
                <div class="card-icon bg-teal">
                    <span class="material-symbols-outlined">bolt</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="get" class="filter-bar" id="ruangFilter">
            <div class="filter-left">
                <div class="year-stepper" data-years="<?= htmlspecialchars(implode(',', $tahunList)) ?>">
                    <button type="button" class="year-step-btn" id="tahunPrev" aria-label="Tahun sebelumnya">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </button>
                    <span class="year-step-label">Tahun <?= (int)$tahun ?></span>
                    <button type="button" class="year-step-btn" id="tahunNext" aria-label="Tahun berikutnya">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                    <input type="hidden" name="tahun" id="tahunValue" value="<?= (int)$tahun ?>">
                </div>
                <div class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" id="search" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari unit atau ruangan">
                </div>
            </div>
            <div class="filter-right">
                <label>Urut Berdasarkan:</label>
                <select class="form-control" name="sort" onchange="this.form.submit()">
                    <option value="desc" <?= $sort === 'desc' ? 'selected' : '' ?>>Total Tertinggi</option>
                    <option value="asc" <?= $sort === 'asc' ? 'selected' : '' ?>>Total Terendah</option>
                </select>
            </div>
        </form>

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
                <span class="badge-info"><?= count($rows) ?> Unit/Ruangan Terdaftar</span>
            </div>

            <!-- Heatmap-style Matrix Table -->
            <?php
                // Lebar kolom didefinisikan sekali, dipakai bersama tabel data & tabel footer,
                // supaya kolomnya selalu sejajar persis (total 100%).
                $colWidths = array_merge([16], array_fill(0, 12, 7));
            ?>

            <!-- Heatmap-style Matrix Table -->
            <div class="table-responsive" id="tableScrollBody">
                <table class="rekap-table">
                    <colgroup>
                        <?php foreach ($colWidths as $w): ?>
                            <col style="width: <?= $w ?>%;">
                        <?php endforeach; ?>
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-left">Nama Unit</th>
                            <?php for ($b = 1; $b <= 12; $b++): ?>
                                <th><?= htmlspecialchars($namaBulan[$b]) ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="13" class="text-left">Belum ada data.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="text-left font-bold"><?= htmlspecialchars($row['nama']) ?></td>
                                <?php for ($b = 1; $b <= 12; $b++): ?>
                                    <?php $v = (int) ($row['bulan'][$b] ?? 0); ?>
                                    <td class="<?= $v > 10 ? 'cell-high' : ($v >= 6 ? 'cell-medium' : '') ?>"><?= $v ?></td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-footer" id="tableFooterScroll">
                <table class="rekap-table rekap-table--footer">
                    <colgroup>
                        <?php foreach ($colWidths as $w): ?>
                            <col style="width: <?= $w ?>%;">
                        <?php endforeach; ?>
                    </colgroup>
                    <tbody>
                        <tr class="row-total">
                            <td class="text-left font-bold text-primary">TOTAL</td>
                            <?php for ($b = 1; $b <= 12; $b++): ?>
                                <td><?= (int) ($totalPerBulan[$b] ?? 0) ?></td>
                            <?php endfor; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
    <script src="<?= BASE_URL ?>/assets/js/ruang.js"></script>
</body>
</html>
