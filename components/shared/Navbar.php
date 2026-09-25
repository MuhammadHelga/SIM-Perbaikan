<?php

$activeMenu = $activeMenu ?? '';

$role = $_SESSION['role'] ?? '';

$menus = [
    'dashboard'   => ['label' => 'Dashboard',        'icon' => 'grid_view',     'url' => BASE_URL . '/dashboard'],
    'laporan'     => ['label' => 'Laporan Kegiatan', 'icon' => 'assignment',    'url' => BASE_URL . '/laporan'],
    'ruang'       => ['label' => 'Rekap Ruang',      'icon' => 'meeting_room',  'url' => BASE_URL . '/ruang'],
    'unit_barang' => ['label' => 'Unit & Barang',    'icon' => 'settings',      'url' => BASE_URL . '/unit'],
];

// Menu Unit & Barang hanya untuk admin
if ($role !== 'admin') {
    unset($menus['unit_barang']);
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>
<header class="app-navbar">
    <div class="app-navbar__accent" aria-hidden="true"></div>
    <div class="app-navbar__inner">
        <a href="<?= BASE_URL ?>/dashboard" class="app-navbar__brand">SIM-Perbaikan</a>

        <nav class="app-navbar__menu">
            <?php foreach ($menus as $key => $menu): ?>
                <a href="<?= htmlspecialchars($menu['url']) ?>"
                   class="app-navbar__link <?= $activeMenu === $key ? 'is-active' : '' ?>">
                    <span class="material-symbols-outlined" aria-hidden="true"><?= $menu['icon'] ?></span>
                    <span><?= htmlspecialchars($menu['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <button type="button" class="theme-toggle" aria-label="Ganti tema">
            <span class="material-symbols-outlined">dark_mode</span>
        </button>

        <span class="app-navbar__user">
            <span class="material-symbols-outlined" aria-hidden="true">account_circle</span>
            <?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['username'] ?? '') ?>
            <?php if ($role !== ''): ?><small>(<?= htmlspecialchars($role) ?>)</small><?php endif; ?>
        </span>

        <form method="post" action="<?= BASE_URL ?>/logout" class="app-navbar__logout-form">
            <button type="submit" class="btn-logout">
                <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                Logout
            </button>
        </form>
    </div>
</header>

<?php if ($flash): ?>
    <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>">
        <span class="flash__text"><?= htmlspecialchars($flash['text']) ?></span>
        <button type="button" class="flash__close" aria-label="Tutup">&times;</button>
    </div>
<?php endif; ?>

<script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
