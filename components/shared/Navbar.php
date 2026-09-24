<?php

$activeMenu = $activeMenu ?? '';

$menus = [
    'dashboard'   => ['label' => 'Dashboard',        'icon' => 'grid_view',     'url' => BASE_URL . '/dashboard'],
    'laporan'     => ['label' => 'Laporan Kegiatan', 'icon' => 'assignment',    'url' => BASE_URL . '/laporan'],
    'ruang'       => ['label' => 'Rekap Ruang',      'icon' => 'meeting_room',  'url' => BASE_URL . '/ruang'],
    'unit_barang' => ['label' => 'Unit & Barang',    'icon' => 'settings',      'url' => BASE_URL . '/unit'],
];

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

        <form method="post" action="<?= BASE_URL ?>/logout" class="app-navbar__logout-form">
            <button type="submit" class="btn-logout">
                <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                Logout
            </button>
        </form>
    </div>
</header>
