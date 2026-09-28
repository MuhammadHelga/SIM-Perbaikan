<?php

/**
 * Rute laporan kegiatan & kerusakan.
 * Variabel dari bootstrap: $router, $conn, $basePath.
 */

// Aksi dinamis (wajib POST + CSRF): /laporan/kirim/{id}, /laporan/terima/{id}, /laporan/hapus/{id}
$router->any('/laporan/{action:kirim|terima|hapus}/{id:\d+}', function (array $params) use ($conn, $basePath) {
    requireLogin($basePath);

    if ($params['action'] === 'hapus') {
        requireRole($basePath, ['admin']);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
        flash('error', 'Permintaan tidak valid. Silakan coba lagi.');
        redirect($basePath . '/laporan');
    }

    require_once __DIR__ . '/../controllers/laporankerusakanController.php';
    $laporanController = new laporankerusakanController($conn);

    $id = (int) $params['id'];
    switch ($params['action']) {
        case 'kirim':
            $laporanController->kirim($id, date('Y-m-d'));
            flash('success', 'Barang ditandai sudah dikirim.');
            break;
        case 'terima':
            $laporanController->terima($id, date('Y-m-d'));
            flash('success', 'Barang ditandai sudah diterima.');
            break;
        case 'hapus':
            $laporanController->destroy($id);
            flash('success', 'Laporan berhasil dihapus.');
            break;
    }

    redirect($basePath . '/laporan');
});

$router->any('/laporan', function () use ($conn, $basePath) {
    requireLogin($basePath);

    $periode      = $_GET['periode'] ?? '';
    $statusFilter = $_GET['status'] ?? '';
    $search       = $_GET['search'] ?? '';

    require_once __DIR__ . '/../services/LaporanService.php';
    $service = new LaporanService($conn);

    $listing = $service->getListing([
        'periode'  => $periode,
        'status'   => $statusFilter,
        'search'   => $search,
        'per_page' => (int) ($_GET['per_page'] ?? 25),
        'page'     => (int) ($_GET['page'] ?? 1),
    ]);

    $laporanList = $listing['rows'];
    $stats       = $listing['stats'];
    $page        = $listing['page'];
    $perPage     = $listing['perPage'];
    $totalPages  = $listing['totalPages'];
    $totalRows   = $listing['totalRows'];

    $ruanganList = $service->getRuangan();
    $barangList  = $service->getBarang();

    require __DIR__ . '/../views/laporan/screens/LaporanView.php';
});

$router->any('/laporan/simpan', function () use ($conn, $basePath) {
    requireLogin($basePath);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrfValid()) {
            flash('error', 'Sesi tidak valid. Silakan coba lagi.');
            redirect($basePath . '/laporan');
        }

        [$data, $errors] = validateLaporanInput($conn, $_POST);

        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect($basePath . '/laporan');
        }

        require_once __DIR__ . '/../controllers/laporankerusakanController.php';
        $laporanController = new laporankerusakanController($conn);

        try {
            $laporanController->store(
                $data['barang_id'],
                $data['unit_id'],
                $data['tanggal'],
                $data['no_seri'],
                $data['rincian'],
                $data['uraian'],
                $data['status'],
                $data['prioritas'],
                $_SESSION['user_id'] ?? null
            );

            flash('success', 'Laporan berhasil disimpan.');
        } catch (mysqli_sql_exception $e) {
            flash('error', 'Gagal menyimpan laporan. Periksa kembali data yang diisi.');
        }
    }

    redirect($basePath . '/laporan');
});

$router->any('/laporan/update', function () use ($conn, $basePath) {
    requireLogin($basePath);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrfValid()) {
            flash('error', 'Sesi tidak valid. Silakan coba lagi.');
            redirect($basePath . '/laporan');
        }

        require_once __DIR__ . '/../controllers/laporankerusakanController.php';
        $laporanController = new laporankerusakanController($conn);

        $id       = (int) ($_POST['id'] ?? 0);
        $existing = $id > 0 ? $laporanController->show($id) : null;

        if (!$existing) {
            flash('error', 'Laporan tidak ditemukan.');
            redirect($basePath . '/laporan');
        }

        // Lengkapi field yang tidak dikirim dengan nilai lama (perilaku edit lama).
        $input = $_POST;
        if (!isset($input['prioritas']) || $input['prioritas'] === '') {
            $input['prioritas'] = $existing['prioritas'];
        }
        if (!isset($input['status']) || $input['status'] === '') {
            $input['status'] = $existing['status_penanganan'];
        }

        [$data, $errors] = validateLaporanInput($conn, $input);

        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect($basePath . '/laporan');
        }

        try {
            $laporanController->update(
                $id,
                $data['barang_id'],
                $data['unit_id'],
                $data['tanggal'],
                $data['no_seri'],
                $data['rincian'],
                $data['uraian'],
                $data['status'],
                $data['prioritas']
            );

            flash('success', 'Laporan berhasil diperbarui.');
        } catch (mysqli_sql_exception $e) {
            flash('error', 'Gagal memperbarui laporan. Periksa kembali data yang diisi.');
        }
    }

    redirect($basePath . '/laporan');
});

$router->any('/laporan/tambah', function () use ($basePath) {
    // Penambahan laporan dilakukan lewat modal di halaman /laporan
    requireLogin($basePath);
    redirect($basePath . '/laporan');
});
