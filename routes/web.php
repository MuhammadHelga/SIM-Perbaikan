// routes/web.php

// Rute untuk Dashboard
$router->add('/dashboard', function() use ($container) {
    $controller = $container->get(DashboardController::class);
    $controller->index();
});

// Rute untuk Laporan Kegiatan
$router->add('/laporan', function() use ($container) {
    // Memanggil method laporan() di userController / LaporanController
    $controller = $container->get(UserController::class);
    $controller->laporan();
});