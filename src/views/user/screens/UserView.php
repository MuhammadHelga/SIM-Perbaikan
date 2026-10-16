<?php
$userList = $userList ?? [];
$total    = count($userList);
$totalAdmin  = count(array_filter($userList, static fn(array $u): bool => ($u['role'] ?? '') === 'admin'));
$totalTeknisi = count(array_filter($userList, static fn(array $u): bool => ($u['role'] ?? '') === 'teknisi'));
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan - Manajemen User</title>
    <link rel="icon" href="<?= BASE_URL ?>/favicon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#00288e">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/unit.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modal.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tambah-laporan-modal.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/theme.css') ?>">
    <style>
        .font-bold { font-weight: 700; }
        .role-tag {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .role-tag--admin { background: #fef3c7; color: #92400e; }
        .role-tag--teknisi { background: #dbeafe; color: #1e40af; }
        .role-tag--kabag { background: #dcfce7; color: #15803d; }
    </style>
</head>
<body>
    <?php $activeMenu = 'user'; include BASE_PATH . '/components/shared/Navbar.php'; ?>
    <main class="container">
        <div class="page-title">
            <h1>Manajemen User</h1>
        </div>

        <div class="cards-grid">
            <div class="card border-blue">
                <div class="card-body">
                    <span class="card-subtitle">TOTAL USER</span>
                    <div class="card-value text-blue"><?= $total ?> <small>Akun</small></div>
                </div>
                <div class="card-icon bg-blue">
                    <span class="material-symbols-outlined">group</span>
                </div>
            </div>

            <div class="card border-teal">
                <div class="card-body">
                    <span class="card-subtitle">TOTAL IT</span>
                    <div class="card-value text-teal"><?= $totalAdmin ?> <small>Akun</small></div>
                </div>
                <div class="card-icon bg-teal">
                    <span class="material-symbols-outlined">shield_person</span>
                </div>
            </div>

            <div class="card border-purple">
                <div class="card-body">
                    <span class="card-subtitle">TOTAL TEKNISI</span>
                    <div class="card-value text-purple"><?= $totalTeknisi ?> <small>Akun</small></div>
                </div>
                <div class="card-icon bg-purple">
                    <span class="material-symbols-outlined">engineering</span>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <div class="header-title">
                    <div class="icon-badge bg-blue"><span class="material-symbols-outlined">group</span></div>
                    <h3>Daftar User</h3>
                </div>
                <button class="btn btn-primary" onclick="openUserModal()">
                    <span class="material-symbols-outlined">add_circle</span> Tambah User
                </button>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="6%">No</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$userList): ?>
                            <tr><td colspan="6" class="text-center">Belum ada user.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($userList as $i => $u): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><span class="font-bold"><?= htmlspecialchars($u['nama'] ?? '') ?></span></td>
                                <td><?= htmlspecialchars($u['jabatan'] ?? '') !== '' ? htmlspecialchars($u['jabatan']) : '-' ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td>
                                    <span class="role-tag role-tag--<?= in_array($u['role'], ['admin', 'teknisi', 'kabag'], true) ? $u['role'] : 'teknisi' ?>">
                                        <?= htmlspecialchars(['admin' => 'IT', 'teknisi' => 'Teknisi', 'kabag' => 'Kabag. Fasilitas dan Keselamatan'][$u['role']] ?? $u['role']) ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <button type="button" class="icon-action text-warning" title="Edit"
                                        data-id="<?= (int)$u['id'] ?>"
                                        data-nama="<?= htmlspecialchars($u['nama'] ?? '', ENT_QUOTES) ?>"
                                        data-jabatan="<?= htmlspecialchars($u['jabatan'] ?? '', ENT_QUOTES) ?>"
                                        data-username="<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>"
                                        data-role="<?= htmlspecialchars($u['role'], ENT_QUOTES) ?>"
                                        onclick="openUserEdit(this)">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button type="button" class="icon-action text-danger" title="Hapus"
                                        onclick="confirmUserDelete(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
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

    <!-- Modal tambah/edit user -->
    <div class="modal-overlay" id="modal-user">
        <div class="form-card">
            <div class="form-header">
                <div class="header-icon">
                    <span class="material-symbols-outlined" id="userModalIcon"><!-- diisi JS --></span>
                </div>
                <div class="header-text">
                    <h2 id="userModalTitle"><!-- diisi JS --></h2>
                    <p id="userModalDesc"><!-- diisi JS --></p>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('modal-user')" aria-label="Tutup">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form method="post" class="form-body" id="form-user" action="<?= BASE_URL ?>/user/simpan">
                <?= csrfField() ?>
                <input type="hidden" name="id" id="user-id" value="">

                <div class="form-row">
                    <div class="form-group">
                        <label for="user-nama">Nama Lengkap</label>
                        <div class="input-icon-wrapper">
                            <span class="material-symbols-outlined field-icon">person</span>
                            <input type="text" id="user-nama" name="nama" placeholder="Nama lengkap" maxlength="100" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="user-jabatan">Jabatan</label>
                        <div class="input-icon-wrapper">
                            <span class="material-symbols-outlined field-icon">badge</span>
                            <input type="text" id="user-jabatan" name="jabatan" placeholder="cth: Staf IT / Kepala Ruangan" maxlength="100">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="user-username">Username</label>
                        <div class="input-icon-wrapper">
                            <span class="material-symbols-outlined field-icon">account_circle</span>
                            <input type="text" id="user-username" name="username" placeholder="Username login" maxlength="50" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="user-role">Role</label>
                        <div class="input-icon-wrapper">
                            <span class="material-symbols-outlined field-icon">shield_person</span>
                            <select id="user-role" name="role" required>
                                <option value="teknisi">Teknisi</option>
                                <option value="admin">IT</option>
                                <option value="kabag">Kabag. Fasilitas dan Keselamatan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="user-password">Password</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">lock</span>
                        <input type="password" id="user-password" name="password" placeholder="Minimal 6 karakter" minlength="6" autocomplete="new-password">
                    </div>
                    <small id="userPasswordHint" style="display:block; margin-top:6px; color:#777; font-size:12px;"></small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modal-user')">Batal</button>
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
    <script src="<?= BASE_URL ?>/assets/js/user.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/user.js') ?>"></script>
</body>
</html>
