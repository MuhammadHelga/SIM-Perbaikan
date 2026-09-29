<?php
$subnetList   = $subnetList ?? [];
$alokasiCount = $alokasiCount ?? 0;
$total        = count($subnetList);
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Kelola Subnet</title>
    <link rel="icon" href="<?= BASE_URL ?>/favicon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#00288e">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/unit.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css?v=<?= filemtime(dirname(__DIR__, 4) . '/public/assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modal.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tambah-laporan-modal.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css">
</head>
<body>
    <?php $activeMenu = 'subnet'; include BASE_PATH . '/components/shared/Navbar.php'; ?>
    <main class="container">
        <div class="page-title">
            <h1>Kelola Subnet Jaringan</h1>
        </div>

        <div class="cards-grid">
            <div class="card border-blue">
                <div class="card-body">
                    <span class="card-subtitle">TOTAL SUBNET</span>
                    <div class="card-value text-blue"><?= $total ?> <small>Subnet</small></div>
                </div>
                <div class="card-icon bg-blue">
                    <span class="material-symbols-outlined">account_tree</span>
                </div>
            </div>

            <div class="card border-teal">
                <div class="card-body">
                    <span class="card-subtitle">TOTAL ALOKASI IP</span>
                    <div class="card-value text-teal"><?= (int)$alokasiCount ?> <small>Host IP</small></div>
                </div>
                <div class="card-icon bg-teal">
                    <span class="material-symbols-outlined">lan</span>
                </div>
            </div>
        </div>


        <div class="table-card">
            <div class="table-header">
                <div class="header-title">
                    <div class="icon-badge bg-blue"><span class="material-symbols-outlined">lan</span></div>
                    <h3>Daftar Subnet</h3>
                </div>
                <button class="btn btn-primary" onclick="openSubnetModal()">
                    <span class="material-symbols-outlined">add_circle</span> Tambah Subnet
                    </button>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="6%">No</th>
                            <th>CIDR</th>
                            <th>Prefix</th>
                            <th>Gateway</th>
                            <th>Mask</th>
                            <th>Keterangan</th>
                            <th width="15%" class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$subnetList): ?>
                            <tr><td colspan="7">Belum ada subnet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($subnetList as $i => $s): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><span class="font-bold"><?= htmlspecialchars($s['cidr']) ?></span></td>
                                <td><?= htmlspecialchars($s['prefix']) ?></td>
                                <td><?= htmlspecialchars($s['gateway'] ?? '') !== '' ? htmlspecialchars($s['gateway']) : '-' ?></td>
                                <td><?= htmlspecialchars($s['mask'] ?? '') !== '' ? htmlspecialchars($s['mask']) : '-' ?></td>
                                <td><?= htmlspecialchars($s['keterangan'] ?? '') !== '' ? htmlspecialchars($s['keterangan']) : '-' ?></td>
                                <td class="action-buttons">
                                    <button type="button" class="icon-action text-warning" title="Edit"
                                        data-id="<?= (int)$s['id'] ?>"
                                        data-cidr="<?= htmlspecialchars($s['cidr'], ENT_QUOTES) ?>"
                                        data-prefix="<?= htmlspecialchars($s['prefix'], ENT_QUOTES) ?>"
                                        data-gateway="<?= htmlspecialchars($s['gateway'] ?? '', ENT_QUOTES) ?>"
                                        data-mask="<?= htmlspecialchars($s['mask'] ?? '', ENT_QUOTES) ?>"
                                        data-keterangan="<?= htmlspecialchars($s['keterangan'] ?? '', ENT_QUOTES) ?>"
                                        onclick="openSubnetEdit(this)">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button type="button" class="icon-action text-danger" title="Hapus"
                                        onclick="confirmSubnetDelete(<?= (int)$s['id'] ?>, '<?= htmlspecialchars($s['cidr'], ENT_QUOTES) ?>')">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal tambah/edit subnet -->
    <div class="modal-overlay" id="modal-subnet">
        <div class="form-card">
            <div class="form-header">
                <div class="header-icon">
                    <span class="material-symbols-outlined" id="subnetModalIcon">account_tree</span>
                </div>
                <div class="header-text">
                    <h2 id="subnetModalTitle">Tambah Subnet</h2>
                    <p id="subnetModalDesc">Tambah data subnet jaringan baru</p>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('modal-subnet')" aria-label="Tutup">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form method="post" class="form-body" id="form-subnet">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="subnet-id" value="">

                <div class="form-row">
                    <div class="form-group">
                        <label for="subnet-cidr">CIDR</label>
                        <div class="input-icon-wrapper">
                            <span class="material-symbols-outlined field-icon">lan</span>
                            <input type="text" id="subnet-cidr" name="cidr" placeholder="Contoh: 192.100.99.0/24" maxlength="18" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="subnet-prefix">Prefix</label>
                        <div class="input-icon-wrapper">
                            <span class="material-symbols-outlined field-icon">tag</span>
                            <input type="text" id="subnet-prefix" name="prefix" placeholder="Contoh: 192.100.99" maxlength="15" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="subnet-gateway">Gateway</label>
                        <div class="input-icon-wrapper">
                            <span class="material-symbols-outlined field-icon">router</span>
                            <input type="text" id="subnet-gateway" name="gateway" placeholder="Contoh: 192.100.99.1" maxlength="15">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="subnet-mask">Subnet Mask</label>
                        <div class="input-icon-wrapper">
                            <span class="material-symbols-outlined field-icon">grid_on</span>
                            <input type="text" id="subnet-mask" name="mask" placeholder="Contoh: 255.255.255.0" maxlength="15">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="subnet-keterangan">Keterangan</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">notes</span>
                        <input type="text" id="subnet-keterangan" name="keterangan" placeholder="Contoh: Jaringan lokal RS" maxlength="120">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modal-subnet')">Batal</button>
                    <button type="submit" class="btn-simpan">
                        <span class="material-symbols-outlined">save</span> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal konfirmasi -->
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

    <script>window.BASE_URL = <?= json_encode(BASE_URL) ?>; window.CSRF_TOKEN = <?= json_encode(csrfToken()) ?>;</script>
    <script src="<?= BASE_URL ?>/assets/js/subnet.js"></script>
</body>
</html>
