<?php
$ruanganList  = $ruanganList ?? [];
$barangList   = $barangList ?? [];
$totalRuangan = count($ruanganList);
$totalBarang  = count($barangList);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Unit & Barang</title>
    <!-- Font Inter & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/unit.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modal.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tambah-laporan-modal.css">
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
                    <button class="btn btn-primary" onclick="openUnitModal('ruangan')">
                        <span class="material-symbols-outlined">add_circle</span> Tambah Unit
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="10%">No</th>
                                <th width="25%">Kode</th>
                                <th width="45%">Nama Ruang</th>
                                <th width="20%" class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$ruanganList): ?>
                                <tr><td colspan="4">Belum ada data.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($ruanganList as $i => $r): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($r['kode_ruangan'] ?? '') !== '' ? htmlspecialchars($r['kode_ruangan']) : '-' ?></td>
                                    <td><?= htmlspecialchars($r['nama_ruangan']) ?></td>
                                    <td class="action-buttons">
                                        <button type="button" class="icon-action text-warning" title="Edit"
                                            data-jenis="ruangan"
                                            data-id="<?= (int)$r['id'] ?>"
                                            data-kode="<?= htmlspecialchars($r['kode_ruangan'] ?? '', ENT_QUOTES) ?>"
                                            data-nama="<?= htmlspecialchars($r['nama_ruangan'], ENT_QUOTES) ?>"
                                            onclick="openUnitEdit(this)">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                        <button type="button" class="icon-action text-danger" title="Hapus"
                                            onclick="confirmUnitDelete('ruangan', <?= (int)$r['id'] ?>, '<?= htmlspecialchars($r['nama_ruangan'], ENT_QUOTES) ?>')">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
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
                    <button class="btn btn-primary" onclick="openUnitModal('barang')">
                        <span class="material-symbols-outlined">add_circle</span> Tambah Barang
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="10%">No</th>
                                <th width="25%">Kode</th>
                                <th width="45%">Nama Barang</th>
                                <th width="20%" class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$barangList): ?>
                                <tr><td colspan="4">Belum ada data.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($barangList as $i => $b): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($b['kode_barang'] ?? '') !== '' ? htmlspecialchars($b['kode_barang']) : '-' ?></td>
                                    <td><?= htmlspecialchars($b['nama_barang']) ?></td>
                                    <td class="action-buttons">
                                        <button type="button" class="icon-action text-warning" title="Edit"
                                            data-jenis="barang"
                                            data-id="<?= (int)$b['id'] ?>"
                                            data-kode="<?= htmlspecialchars($b['kode_barang'] ?? '', ENT_QUOTES) ?>"
                                            data-nama="<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>"
                                            onclick="openUnitEdit(this)">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                        <button type="button" class="icon-action text-danger" title="Hapus"
                                            onclick="confirmUnitDelete('barang', <?= (int)$b['id'] ?>, '<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>')">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- Modal tambah/edit unit & barang (gaya sama seperti modal laporan) -->
    <div class="modal-overlay" id="modal-unit">
        <div class="form-card">
            <div class="form-header">
                <div class="header-icon">
                    <span class="material-symbols-outlined" id="unitModalIcon">meeting_room</span>
                </div>
                <div class="header-text">
                    <h2 id="unitModalTitle">Tambah Unit</h2>
                    <p id="unitModalDesc">Tambah data unit/ruangan baru</p>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('modal-unit')" aria-label="Tutup">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form method="post" class="form-body" id="form-unit">
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

    <!-- Modal konfirmasi (sama seperti halaman laporan) -->
    <div class="modal-overlay" id="modal-konfirmasi">
        <div class="confirm-card">
            <div class="confirm-card__icon">
                <span class="material-symbols-outlined" id="confirmIcon">delete</span>
            </div>
            <h3 class="confirm-card__title" id="confirmTitle">Konfirmasi</h3>
            <p class="confirm-card__text" id="confirmText">Apakah kamu yakin?</p>
            <div class="confirm-card__actions">
                <button type="button" class="confirm-btn confirm-btn--cancel" onclick="closeModal('modal-konfirmasi')">Batal</button>
                <button type="button" class="confirm-btn confirm-btn--danger" id="confirmActionBtn">Ya, Hapus</button>
            </div>
        </div>
    </div>

    <script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
    <script src="<?= BASE_URL ?>/assets/js/unit.js"></script>
</body>
</html>
