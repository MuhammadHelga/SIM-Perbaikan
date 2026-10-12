<?php
$ruanganList  = $ruanganList ?? [];
$barangList   = $barangList ?? [];
$asetList     = $asetList ?? [];
$ruanganAktifList = $ruanganAktifList ?? $ruanganList;
$barangAktifList  = $barangAktifList ?? $barangList;

$totalRuangan = 0;
foreach ($ruanganList as $r) {
    if ((int) ($r['aktif'] ?? 1) === 1) {
        $totalRuangan++;
    }
}
$totalBarang = 0;
foreach ($barangList as $b) {
    if ((int) ($b['aktif'] ?? 1) === 1) {
        $totalBarang++;
    }
}
$totalAset = 0;
foreach ($asetList as $a) {
    if ((int) ($a['aktif'] ?? 1) === 1) {
        $totalAset++;
    }
}
$totalAsetNonaktif = count($asetList) - $totalAset;
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Unit & Barang</title>
    <link rel="icon" href="<?= BASE_URL ?>/favicon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#00288e">
    <!-- Font Poppins & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/unit.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/unit.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css?v=<?= filemtime(dirname(__DIR__, 4) . '/public/assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modal.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tambah-laporan-modal.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/theme.css') ?>">
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
                    <div class="card-value text-blue"><?= $totalRuangan ?> <small>Unit/Ruang</small></div>
                </div>
                <div class="card-icon bg-blue">
                    <span class="material-symbols-outlined">meeting_room</span>
                </div>
            </div>

            <div class="card border-teal">
                <div class="card-body">
                    <span class="card-subtitle">TOTAL BARANG</span>
                    <div class="card-value text-teal"><?= $totalBarang ?> <small>Barang</small></div>
                </div>
                <div class="card-icon bg-teal">
                    <span class="material-symbols-outlined">inventory_2</span>
                </div>
            </div>

            <div class="card border-purple">
                <div class="card-body">
                    <span class="card-subtitle">TOTAL ASET</span>
                    <div class="card-value text-purple"><?= $totalAset ?> <small>Unit Fisik aktif<?= $totalAsetNonaktif > 0 ? ' · ' . $totalAsetNonaktif . ' nonaktif' : '' ?></small></div>
                </div>
                <div class="card-icon bg-purple">
                    <span class="material-symbols-outlined">devices</span>
                </div>
            </div>
        </div>

        <!-- Dual Column Table Layout -->
        <div class="tables-grid">

            <!-- Left Side: Daftar Unit/Ruangan -->
            <div class="table-card">
                <div class="table-header">
                    <div class="header-title">
                        <div class="icon-badge bg-blue"><span class="material-symbols-outlined">meeting_room</span></div>
                        <h3>Daftar Unit/Ruangan</h3>
                    </div>
                    <div class="table-header__actions">
                        <label class="toggle-nonaktif" title="Tampilkan unit/ruangan yang sudah dinonaktifkan">
                            <input type="checkbox" id="toggleNonaktifRuangan">
                            <span>Tampilkan nonaktif</span>
                        </label>
                        <button class="btn btn-primary" onclick="openUnitModal('ruangan')">
                            <span class="material-symbols-outlined">add_circle</span> Tambah Unit
                        </button>
                    </div>
                </div>
                <div class="unit-head-wrap" id="ruanganHeadWrap">
                    <table class="data-table">
                        <colgroup>
                            <col style="width:10%">
                            <col style="width:25%">
                            <col style="width:45%">
                            <col style="width:20%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Ruang</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="table-responsive" id="ruanganScroll">
                    <table class="data-table">
                        <colgroup>
                            <col style="width:10%">
                            <col style="width:25%">
                            <col style="width:45%">
                            <col style="width:20%">
                        </colgroup>
                        <tbody>
                            <?php if (!$ruanganList): ?>
                                <tr><td colspan="4">Belum ada data.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($ruanganList as $i => $r): $isAktif = (int) ($r['aktif'] ?? 1) === 1; ?>
                                <tr class="<?= $isAktif ? '' : 'row-inactive' ?>">
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($r['kode_ruangan'] ?? '') !== '' ? htmlspecialchars($r['kode_ruangan']) : '-' ?></td>
                                    <td>
                                        <?= htmlspecialchars($r['nama_ruangan']) ?>
                                        <?php if (!$isAktif): ?><span class="aset-status aset-status--nonaktif">Nonaktif</span><?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
                                        <button type="button" class="icon-action text-warning" title="Edit"
                                            data-jenis="ruangan"
                                            data-id="<?= (int)$r['id'] ?>"
                                            data-kode="<?= htmlspecialchars($r['kode_ruangan'] ?? '', ENT_QUOTES) ?>"
                                            data-nama="<?= htmlspecialchars($r['nama_ruangan'], ENT_QUOTES) ?>"
                                            onclick="openUnitEdit(this)">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                        <?php if ($isAktif): ?>
                                            <button type="button" class="icon-action text-danger" title="Nonaktifkan"
                                                onclick="confirmUnitDelete('ruangan', <?= (int)$r['id'] ?>, '<?= htmlspecialchars($r['nama_ruangan'], ENT_QUOTES) ?>')">
                                                <span class="material-symbols-outlined">block</span>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="icon-action text-success" title="Aktifkan kembali"
                                                onclick="confirmUnitRestore('ruangan', <?= (int)$r['id'] ?>, '<?= htmlspecialchars($r['nama_ruangan'], ENT_QUOTES) ?>')">
                                                <span class="material-symbols-outlined">restart_alt</span>
                                            </button>
                                            <button type="button" class="icon-action text-danger" title="Hapus permanen"
                                                onclick="confirmUnitPurge('ruangan', <?= (int)$r['id'] ?>, '<?= htmlspecialchars($r['nama_ruangan'], ENT_QUOTES) ?>')">
                                                <span class="material-symbols-outlined">delete_forever</span>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Side: Daftar Barang -->
            <div class="table-card">
                <div class="table-header">
                    <div class="header-title">
                        <div class="icon-badge bg-teal"><span class="material-symbols-outlined">inventory_2</span></div>
                        <h3>Daftar Barang</h3>
                    </div>
                    <div class="table-header__actions">
                        <label class="toggle-nonaktif" title="Tampilkan barang yang sudah dinonaktifkan">
                            <input type="checkbox" id="toggleNonaktifBarang">
                            <span>Tampilkan nonaktif</span>
                        </label>
                        <button class="btn btn-primary" onclick="openUnitModal('barang')">
                            <span class="material-symbols-outlined">add_circle</span> Tambah Barang
                        </button>
                    </div>
                </div>
                <div class="unit-head-wrap" id="barangHeadWrap">
                    <table class="data-table">
                        <colgroup>
                            <col style="width:10%">
                            <col style="width:25%">
                            <col style="width:45%">
                            <col style="width:20%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="table-responsive" id="barangScroll">
                    <table class="data-table">
                        <colgroup>
                            <col style="width:10%">
                            <col style="width:25%">
                            <col style="width:45%">
                            <col style="width:20%">
                        </colgroup>
                        <tbody>
                            <?php if (!$barangList): ?>
                                <tr><td colspan="4">Belum ada data.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($barangList as $i => $b): $isAktif = (int) ($b['aktif'] ?? 1) === 1; ?>
                                <tr class="<?= $isAktif ? '' : 'row-inactive' ?>">
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($b['kode_barang'] ?? '') !== '' ? htmlspecialchars($b['kode_barang']) : '-' ?></td>
                                    <td>
                                        <?= htmlspecialchars($b['nama_barang']) ?>
                                        <?php if (!$isAktif): ?><span class="aset-status aset-status--nonaktif">Nonaktif</span><?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
                                        <button type="button" class="icon-action text-warning" title="Edit"
                                            data-jenis="barang"
                                            data-id="<?= (int)$b['id'] ?>"
                                            data-kode="<?= htmlspecialchars($b['kode_barang'] ?? '', ENT_QUOTES) ?>"
                                            data-nama="<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>"
                                            onclick="openUnitEdit(this)">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                        <?php if ($isAktif): ?>
                                            <button type="button" class="icon-action text-danger" title="Nonaktifkan"
                                                onclick="confirmUnitDelete('barang', <?= (int)$b['id'] ?>, '<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>')">
                                                <span class="material-symbols-outlined">block</span>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="icon-action text-success" title="Aktifkan kembali"
                                                onclick="confirmUnitRestore('barang', <?= (int)$b['id'] ?>, '<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>')">
                                                <span class="material-symbols-outlined">restart_alt</span>
                                            </button>
                                            <button type="button" class="icon-action text-danger" title="Hapus permanen"
                                                onclick="confirmUnitPurge('barang', <?= (int)$b['id'] ?>, '<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>')">
                                                <span class="material-symbols-outlined">delete_forever</span>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Full width: Daftar Aset Barang -->
        <div class="table-card table-card--full">
            <div class="table-header">
                <div class="header-title">
                    <div class="icon-badge bg-purple"><span class="material-symbols-outlined">devices</span></div>
                    <h3>Daftar Aset Barang</h3>
                </div>
                <div class="table-header__actions">
                    <label class="toggle-nonaktif" title="Tampilkan aset yang sudah dinonaktifkan">
                        <input type="checkbox" id="toggleNonaktifAset">
                        <span>Tampilkan nonaktif</span>
                    </label>
                    <button class="btn btn-primary" onclick="openAsetModal()">
                        <span class="material-symbols-outlined">add_circle</span> Tambah Aset
                    </button>
                </div>
            </div>
            <div class="unit-head-wrap" id="asetHeadWrap">
                <table class="data-table">
                    <colgroup>
                        <col style="width:6%">
                        <col style="width:18%">
                        <col style="width:18%">
                        <col style="width:16%">
                        <col style="width:22%">
                        <col style="width:14%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Ruang</th>
                            <th>Barang</th>
                            <th>Serial Number</th>
                            <th>Tipe / Model</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class="table-responsive" id="asetScroll">
                <table class="data-table">
                    <colgroup>
                        <col style="width:6%">
                        <col style="width:18%">
                        <col style="width:18%">
                        <col style="width:16%">
                        <col style="width:22%">
                        <col style="width:14%">
                    </colgroup>
                    <tbody>
                        <?php if (!$asetList): ?>
                            <tr><td class="text-center" colspan="6">Belum ada aset. Tambahkan aset fisik (ruangan + barang + serial) agar pilihan di form laporan otomatis tersaring.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($asetList as $i => $a): ?>
                            <?php
                                $isAktif  = (int) ($a['aktif'] ?? 1) === 1;
                                $serial   = (string) ($a['serial_number'] ?? '');
                                $tipe     = (string) ($a['tipe'] ?? '');
                                $labelAks = $serial !== '' ? $serial : ($tipe !== '' ? $tipe : '#' . (int) $a['id']);
                            ?>
                            <tr class="<?= $isAktif ? '' : 'row-inactive' ?>">
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($a['nama_ruangan']) ?></td>
                                <td><?= htmlspecialchars($a['nama_barang']) ?></td>
                                <td>
                                    <?= $serial !== '' ? htmlspecialchars($serial) : '-' ?>
                                    <?php if (!$isAktif): ?>
                                        <span class="aset-status aset-status--nonaktif">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $tipe !== '' ? htmlspecialchars($tipe) : '-' ?></td>
                                <td class="action-buttons">
                                    <button type="button" class="icon-action text-warning" title="Edit"
                                        data-id="<?= (int)$a['id'] ?>"
                                        data-id-ruangan="<?= (int)$a['id_ruangan'] ?>"
                                        data-id-barang="<?= (int)$a['id_barang'] ?>"
                                        data-serial="<?= htmlspecialchars($serial, ENT_QUOTES) ?>"
                                        data-tipe="<?= htmlspecialchars($tipe, ENT_QUOTES) ?>"
                                        data-keterangan="<?= htmlspecialchars((string)($a['keterangan'] ?? ''), ENT_QUOTES) ?>"
                                        onclick="openAsetEdit(this)">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <?php if ($isAktif): ?>
                                        <button type="button" class="icon-action text-danger" title="Nonaktifkan"
                                            onclick="confirmAsetDelete(<?= (int)$a['id'] ?>, '<?= htmlspecialchars($labelAks, ENT_QUOTES) ?>')">
                                            <span class="material-symbols-outlined">block</span>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="icon-action text-success" title="Aktifkan kembali"
                                            onclick="confirmAsetRestore(<?= (int)$a['id'] ?>, '<?= htmlspecialchars($labelAks, ENT_QUOTES) ?>')">
                                            <span class="material-symbols-outlined">restart_alt</span>
                                        </button>
                                        <button type="button" class="icon-action text-danger" title="Hapus permanen"
                                            onclick="confirmAsetPurge(<?= (int)$a['id'] ?>, '<?= htmlspecialchars($labelAks, ENT_QUOTES) ?>')">
                                            <span class="material-symbols-outlined">delete_forever</span>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal tambah/edit unit & barang (gaya sama seperti modal laporan) -->
    <div class="modal-overlay" id="modal-unit">
        <div class="form-card">
            <div class="form-header">
                <div class="header-icon">
                    <span class="material-symbols-outlined" id="unitModalIcon"><!-- diisi JS --></span>
                </div>
                <div class="header-text">
                    <h2 id="unitModalTitle"><!-- diisi JS --></h2>
                    <p id="unitModalDesc"><!-- diisi JS --></p>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('modal-unit')" aria-label="Tutup">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form method="post" class="form-body" id="form-unit">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="unit-id" value="">

                <div class="form-group">
                    <label for="unit-kode">Kode</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">tag</span>
                        <input type="text" id="unit-kode" name="kode" placeholder="Contoh: POLI / BRG-01" maxlength="30">
                    </div>
                </div>

                <div class="form-group">
                    <label for="unit-nama">Nama</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">label</span>
                        <input type="text" id="unit-nama" name="nama" placeholder="Nama unit / barang" maxlength="100" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modal-unit')">Batal</button>
                    <button type="submit" class="btn-simpan">
                        <span class="material-symbols-outlined">save</span> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal tambah/edit aset barang (unit fisik per ruangan) -->
    <div class="modal-overlay" id="modal-aset">
        <div class="form-card">
            <div class="form-header">
                <div class="header-icon">
                    <span class="material-symbols-outlined" id="asetModalIcon"><!-- diisi JS --></span>
                </div>
                <div class="header-text">
                    <h2 id="asetModalTitle"><!-- diisi JS --></h2>
                    <p id="asetModalDesc"><!-- diisi JS --></p>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('modal-aset')" aria-label="Tutup">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form method="post" class="form-body" id="form-aset">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="aset-id" value="">

                <div class="form-group">
                    <label for="aset-ruangan">Unit/Ruangan</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">meeting_room</span>
                        <select id="aset-ruangan" name="id_ruangan" required>
                            <option value="" disabled selected>Pilih Unit / Ruang</option>
                            <?php foreach ($ruanganAktifList as $r): ?>
                                <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['nama_ruangan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="aset-barang">Jenis Barang</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">inventory_2</span>
                        <select id="aset-barang" name="id_barang" required>
                            <option value="" disabled selected>Pilih Jenis Barang</option>
                            <?php foreach ($barangAktifList as $b): ?>
                                <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['nama_barang']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="aset-serial">Serial Number <span class="form-label-optional">(opsional)</span></label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">tag</span>
                        <input type="text" id="aset-serial" name="serial_number" placeholder="Contoh: SN-1234" maxlength="50" autocomplete="off">
                    </div>
                    <small class="form-hint">Unik bila diisi; kosongkan jika unit tidak punya nomor seri.</small>
                </div>

                <div class="form-group">
                    <label for="aset-tipe">Tipe / Model <span class="form-label-optional">(opsional)</span></label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">build</span>
                        <input type="text" id="aset-tipe" name="tipe" placeholder="Contoh: Epson L3110 / Dell OptiPlex" maxlength="80">
                    </div>
                </div>

                <div class="form-group">
                    <label for="aset-keterangan">Keterangan <span class="form-label-optional">(opsional)</span></label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">notes</span>
                        <input type="text" id="aset-keterangan" name="keterangan" placeholder="Catatan tambahan" maxlength="200">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modal-aset')">Batal</button>
                    <button type="submit" class="btn-simpan">
                        <span class="material-symbols-outlined">save</span> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal konfirmasi (sama seperti halaman laporan) -->
    <div class="modal-overlay" id="modal-konfirmasi">
        <div class="confirm-card">
            <div class="confirm-card__icon">
                <span class="material-symbols-outlined" id="confirmIcon"><!-- diisi JS --></span>
            </div>
            <h3 class="confirm-card__title" id="confirmTitle"><!-- diisi JS --></h3>
            <p class="confirm-card__text" id="confirmText"><!-- diisi JS --></p>
            <div class="confirm-card__actions">
                <button type="button" class="confirm-btn confirm-btn--cancel" onclick="closeModal('modal-konfirmasi')">Batal</button>
                <button type="button" class="confirm-btn confirm-btn--danger" id="confirmActionBtn"><!-- diisi JS --></button>
            </div>
        </div>
    </div>

    <script>window.BASE_URL = <?= json_encode(BASE_URL) ?>; window.CSRF_TOKEN = <?= json_encode(csrfToken()) ?>;</script>
    <script src="<?= BASE_URL ?>/assets/js/unit.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/unit.js') ?>"></script>
</body>
</html>
