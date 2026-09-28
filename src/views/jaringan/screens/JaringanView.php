<?php

$subnet        = $subnet ?? ['gateway' => '192.100.99.1', 'mask' => '255.255.255.0', 'prefix' => '192.100.99'];
$stats         = $stats ?? ['total_unit' => 0, 'ip_terpakai' => 0, 'host_kosong' => 0, 'uptime_percent' => 0, 'online' => 0, 'offline' => 0];
$occupancy     = $occupancy ?? ['core' => 9, 'terisi' => 0, 'kosong' => 245, 'tersedia_persen' => 100, 'map' => []];
$komputerList  = $komputerList ?? [];
$search        = $search ?? '';
$filterUnit    = $filterUnit ?? '';
$filterStatus  = $filterStatus ?? '';
$page          = $page ?? 1;
$perPage       = $perPage ?? 9;
$totalRows     = $totalRows ?? count($komputerList);
$totalPages    = max(1, (int)ceil($totalRows / $perPage));

$utilisasi = $stats['total_unit'] > 0
    ? round(($stats['ip_terpakai'] / ($stats['ip_terpakai'] + $stats['host_kosong'])) * 100, 1)
    : 0;
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Jaringan &amp; Alokasi IP</title>
    <link rel="icon" href="<?= BASE_URL ?>/favicon.svg" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/jaringan.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modal.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css">
</head>
<body>
    <?php $activeMenu = 'jaringan'; include BASE_PATH . '/components/shared/Navbar.php'; ?>

    <main class="jr-wrap">

        <div class="jr-head">
            <div class="jr-head__text">
                <h1>Manajemen Jaringan &amp; Alokasi IP Komputer</h1>
                <p>
                    Pemetaan alamat IP jaringan lokal rumah sakit subnet
                    <strong><?= htmlspecialchars($subnet['prefix']) ?>.0/24</strong>
                    untuk setiap komputer unit kerja dan instalasi operasional.
                </p>
            </div>
            <div class="jr-subnet-badge">
                <span class="material-symbols-outlined">lan</span>
                <div class="jr-subnet-badge__grid">
                    <div>
                        <span class="jr-subnet-badge__label">Subnet Gateway</span>
                        <span class="jr-subnet-badge__value"><?= htmlspecialchars($subnet['gateway']) ?></span>
                    </div>
                    <div>
                        <span class="jr-subnet-badge__label">Subnet Mask &amp; Scope</span>
                        <span class="jr-subnet-badge__value"><?= htmlspecialchars($subnet['mask']) ?> (/24)</span>
                    </div>
                    <div>
                        <span class="jr-subnet-badge__label">Fixed Octet Prefix</span>
                        <span class="jr-subnet-badge__value jr-subnet-badge__value--highlight"><?= htmlspecialchars($subnet['prefix']) ?>.xx</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="jr-stat-row">
            <div class="jr-stat-card">
                <div class="jr-stat-card__icon jr-stat-card__icon--purple">
                    <span class="material-symbols-outlined">computer</span>
                </div>
                <div>
                    <p class="jr-stat-card__label">Total Komputer Terdaftar</p>
                    <p class="jr-stat-card__value"><?= (int)$stats['total_unit'] ?> <span>Unit Terpasang</span></p>
                    <p class="jr-stat-card__note">Semua workstation aktif RS</p>
                </div>
            </div>

            <div class="jr-stat-card">
                <div class="jr-stat-card__icon jr-stat-card__icon--blue">
                    <span class="material-symbols-outlined">bar_chart</span>
                </div>
                <div class="jr-stat-card__body">
                    <p class="jr-stat-card__label">IP Terpakai / Assigned</p>
                    <p class="jr-stat-card__value"><?= (int)$stats['ip_terpakai'] ?> <span>Host Terdaftar</span></p>
                    <div class="jr-progress">
                        <div class="jr-progress__bar" style="width: <?= $utilisasi ?>%;"></div>
                    </div>
                    <p class="jr-stat-card__note"><?= $utilisasi ?>% Utilisasi Subnet /24</p>
                </div>
            </div>

            <div class="jr-stat-card">
                <div class="jr-stat-card__icon jr-stat-card__icon--green">
                    <span class="material-symbols-outlined">check_circle</span>
                </div>
                <div>
                    <p class="jr-stat-card__label">Host IP Tersedia</p>
                    <p class="jr-stat-card__value"><?= (int)$stats['host_kosong'] ?> <span>Host Kosong</span></p>
                    <p class="jr-stat-card__note">Range aman untuk alokasi baru</p>
                </div>
            </div>
        </div>

        <form method="get" class="jr-filter-bar" id="jaringanFilterForm">
            <div class="jr-search">
                <span class="material-symbols-outlined">search</span>
                <input type="text" name="search" id="search" placeholder="Cari Nama Unit, Nama Komputer, atau Host IP..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="jr-filter-actions">
                <button type="button" class="jr-btn jr-btn--outline" id="btnExportCsv">
                    <span class="material-symbols-outlined">download</span> Export CSV
                </button>
                <button type="button" class="jr-btn jr-btn--primary" onclick="openAlokasiModal()">
                    <span class="material-symbols-outlined">add</span> Alokasi IP
                </button>
            </div>
        </form>

        <div class="jr-table-card">
            <div class="jr-table-card__head">
                <span class="material-symbols-outlined">table_rows</span>
                <h3>Tabel Pemetaan Komputer Unit &amp; Alokasi Host IP</h3>
                <span class="jr-table-card__subnet">Subnet: <?= htmlspecialchars($subnet['prefix']) ?>.[host]</span>
            </div>

            <div class="jr-table-scroll">
                <table class="jr-table">
                    <thead>
                        <tr>
                            <th class="jr-col-no">No</th>
                            <th>Nama Unit / Ruangan</th>
                            <th>Hostname Komputer</th>
                            <th>Alamat IP</th>
                            <th class="jr-col-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$komputerList): ?>
                            <tr><td colspan="5" class="jr-empty">Belum ada data alokasi IP.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($komputerList as $i => $row): ?>
                            <tr>
                                <td class="jr-col-no"><?= str_pad((string)(($page - 1) * $perPage + $i + 1), 2, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <span class="jr-unit-name"><?= htmlspecialchars($row['unit']) ?></span>
                                    <span class="jr-unit-loc"><?= htmlspecialchars($row['lokasi']) ?></span>
                                </td>
                                <td><span class="jr-hostname"><?= htmlspecialchars($row['hostname']) ?></span></td>
                                <td>
                                    <span class="jr-ip">
                                        <span class="jr-ip__prefix"><?= htmlspecialchars($subnet['prefix']) ?>.</span>
                                        <span class="jr-ip__octet">
                                            <?= (int)$row['host_octet'] ?>
                                        </span>
                                    </span>
                                </td>
                                <td class="jr-col-aksi">
                                    <div class="jr-action-icons">
                                        <button type="button" class="jr-icon-btn jr-icon-btn--edit" title="Edit alokasi"
                                                onclick="openEditAlokasiModal(<?= (int)$row['id'] ?>)">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="jr-table-footer">
                <span>Menampilkan <?= $totalRows ?> alokasi komputer terpasang</span>
            </div>
        </div>

    </main>

    <?php include dirname(__DIR__) . '/modals/AlokasiIpModal.php'; ?>

    <div class="modal-overlay" id="modal-konfirmasi">
        <div class="confirm-card">
            <div class="confirm-card__icon"><span class="material-symbols-outlined" id="confirmIcon">help</span></div>
            <h3 class="confirm-card__title" id="confirmTitle">Konfirmasi</h3>
            <p class="confirm-card__text" id="confirmText">Apakah kamu yakin?</p>
            <div class="confirm-card__actions">
                <button type="button" class="confirm-btn confirm-btn--cancel" onclick="closeModal('modal-konfirmasi')">Batal</button>
                <button type="button" class="confirm-btn confirm-btn--primary" id="confirmActionBtn">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>

    <script id="komputerDataJson" type="application/json"><?= json_encode($komputerList, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) ?></script>
    <script>window.BASE_URL = <?= json_encode(BASE_URL) ?>; window.JR_PREFIX = <?= json_encode($subnet['prefix']) ?>;</script>
    <script src="<?= BASE_URL ?>/assets/js/jaringan.js"></script>
</body>
</html>