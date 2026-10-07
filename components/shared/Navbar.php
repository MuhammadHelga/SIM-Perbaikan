<?php

$activeMenu = $activeMenu ?? '';

$role = $_SESSION['role'] ?? '';

$menus = [
    'dashboard'   => ['label' => 'Dashboard',        'icon' => 'grid_view',     'url' => BASE_URL . '/dashboard'],
    'laporan'     => ['label' => 'Laporan Kegiatan', 'icon' => 'assignment',    'url' => BASE_URL . '/laporan'],
    'ruang'       => ['label' => 'Rekap Ruang',      'icon' => 'meeting_room',  'url' => BASE_URL . '/ruang'],
    'unit_barang' => ['label' => 'Unit & Barang',    'icon' => 'settings',      'url' => BASE_URL . '/unit'],
    'jaringan'    => ['label' => 'Jaringan & IP',    'icon' => 'lan',         'url' => BASE_URL . '/jaringan'],
    'subnet'      => ['label' => 'Kelola Subnet',    'icon' => 'account_tree', 'url' => BASE_URL . '/subnet'],
];

// Menu khusus admin
if ($role !== 'admin') {
    unset($menus['unit_barang'], $menus['subnet']);
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>
<script>
/* Terapkan status lipat sedini mungkin supaya tidak berkedip saat load */
try {
    if (localStorage.getItem('sidebarCollapsed') === '1') {
        document.body.classList.add('nav-collapsed');
    }
} catch (e) {}
</script>

<div class="nav-backdrop" id="navBackdrop"></div>

<button type="button" class="nav-toggle" id="navToggle" aria-label="Buka menu">
    <span class="material-symbols-outlined">menu</span>
</button>

<aside class="app-navbar" id="appNavbar">
    <div class="app-navbar__accent" aria-hidden="true"></div>

    <div class="app-navbar__inner">
        <div class="app-navbar__top">
            <a href="<?= BASE_URL ?>/dashboard" class="app-navbar__brand">
                <span class="material-symbols-outlined app-navbar__brand-icon">handyman</span>
                <span class="app-navbar__brand-text">SIM-Perbaikan</span>
            </a>
            <button type="button" class="nav-collapse" id="navCollapse" aria-label="Lipat menu">
                <span class="material-symbols-outlined">chevron_left</span>
            </button>
        </div>

        <div class="app-navbar__user">
            <span class="material-symbols-outlined" aria-hidden="true">account_circle</span>
            <span class="app-navbar__user-text">
                <?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['username'] ?? '') ?>
                <?php if ($role !== ''): ?><small>(<?= htmlspecialchars($role) ?>)</small><?php endif; ?>
            </span>
        </div>

        <nav class="app-navbar__menu">
            <span class="nav-indicator" id="navIndicator" aria-hidden="true"></span>
            <?php foreach ($menus as $key => $menu): ?>
                <a href="<?= htmlspecialchars($menu['url']) ?>"
                   class="app-navbar__link <?= $activeMenu === $key ? 'is-active' : '' ?>"
                   title="<?= htmlspecialchars($menu['label']) ?>">
                    <span class="material-symbols-outlined" aria-hidden="true"><?= $menu['icon'] ?></span>
                    <span class="app-navbar__link-text"><?= htmlspecialchars($menu['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-pet-area">
            <div class="sidebar-pet-scene">
                <span class="sidebar-pet__sparkle sidebar-pet__sparkle--one" aria-hidden="true">✦</span>
                <span class="sidebar-pet__sparkle sidebar-pet__sparkle--two" aria-hidden="true">✦</span>
                <button type="button" class="sidebar-pet sidebar-pet--leader" data-animal="Pak Kiki" data-greeting="Hai, saya mentor IT!" aria-label="Sapa Pak Kiki si pemimpin">
                    <span class="sidebar-pet__bubble" aria-live="polite">Hai, saya mentor IT!</span>
                    <span class="sidebar-pet__leader-crown" aria-hidden="true">👑</span>
                    <span class="sidebar-pet__character sidebar-pet__character--leader" aria-hidden="true">🦁</span>
                </button>
                <div class="sidebar-pet__friends">
                    <button type="button" class="sidebar-pet" data-animal="Rico" aria-label="Sapa Rico">
                        <span class="sidebar-pet__bubble" aria-live="polite">Hai, saya Rico!</span><span class="sidebar-pet__character" aria-hidden="true">🐱</span>
                    </button>
                    <button type="button" class="sidebar-pet" data-animal="Helga" aria-label="Sapa Helga">
                        <span class="sidebar-pet__bubble" aria-live="polite">Hai, saya Helga!</span><span class="sidebar-pet__character" aria-hidden="true">🐨</span>
                    </button>
                    <button type="button" class="sidebar-pet" data-animal="Reza" aria-label="Sapa Reza">
                        <span class="sidebar-pet__bubble" aria-live="polite">Hai, saya Reza!</span><span class="sidebar-pet__character" aria-hidden="true">🐰</span>
                    </button>
                    <button type="button" class="sidebar-pet" data-animal="Maria" aria-label="Sapa Maria">
                        <span class="sidebar-pet__bubble" aria-live="polite">Hai, saya Maria!</span><span class="sidebar-pet__character" aria-hidden="true">🦊</span>
                    </button>
                    <button type="button" class="sidebar-pet" data-animal="Dewi" aria-label="Sapa Dewi">
                        <span class="sidebar-pet__bubble" aria-live="polite">Hai, saya Dewi!</span><span class="sidebar-pet__character" aria-hidden="true">🐸</span>
                    </button>
                </div>
                <span class="sidebar-pet__ground" aria-hidden="true"></span>
            </div>
        </div>

        <div class="app-navbar__foot">
            <button type="button" class="theme-toggle" aria-label="Ganti tema">
                <span class="material-symbols-outlined">dark_mode</span>
            </button>

            <form method="post" action="<?= BASE_URL ?>/logout" class="app-navbar__logout-form">
                <?= csrfField() ?>
                <button type="submit" class="btn-logout">
                    <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                    <span class="btn-logout__text">Logout</span>
                </button>
            </form>
        </div>
    </div>
</aside>

<?php if ($flash): ?>
    <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>">
        <span class="flash__text"><?= htmlspecialchars($flash['text']) ?></span>
        <button type="button" class="flash__close" aria-label="Tutup">&times;</button>
    </div>
<?php endif; ?>

<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= filemtime(dirname(__DIR__, 2) . '/public/assets/js/app.js') ?>" defer></script>
