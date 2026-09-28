<?php

/**
 * Rute jaringan & alokasi IP.
 */

return function (Router $router, mysqli $conn, string $basePath, AuthService $authService): void {
    $router->any('/jaringan', function () use ($conn, $basePath) {
        requireLogin($basePath);

        require_once __DIR__ . '/../controllers/alokasiIpController.php';
        require_once __DIR__ . '/../controllers/subnetController.php';
        require_once __DIR__ . '/../services/JaringanService.php';

        $subnets     = (new subnetController($conn))->index();
        $allocations = (new alokasiIpController($conn))->index();

        $data = (new JaringanService())->build($subnets, $allocations, [
            'subnet' => $_GET['subnet'] ?? '',
            'search' => $_GET['search'] ?? '',
            'unit'   => $_GET['unit'] ?? '',
            'status' => $_GET['status'] ?? '',
            'page'   => (int) ($_GET['page'] ?? 1),
        ]);

        $subnets        = $data['subnets'];
        $selectedSubnet = $data['selectedSubnet'];
        $subnet         = $data['subnet'];
        $subnetCount    = $data['subnetCount'];
        $unitOptions    = $data['unitOptions'];
        $komputerList   = $data['komputerList'];
        $occupancy      = $data['occupancy'];
        $stats          = $data['stats'];
        $search         = $data['search'];
        $filterUnit     = $data['filterUnit'];
        $filterStatus   = $data['filterStatus'];
        $page           = $data['page'];
        $perPage        = $data['perPage'];
        $totalRows      = $data['totalRows'];

        require __DIR__ . '/../views/jaringan/screens/JaringanView.php';
    });

    $router->any('/jaringan/simpan', function () use ($conn, $basePath) {
        requireLogin($basePath);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrfValid()) {
                flash('error', 'Sesi tidak valid. Silakan coba lagi.');
                redirect($basePath . '/jaringan');
            }

            require_once __DIR__ . '/../controllers/alokasiIpController.php';
            require_once __DIR__ . '/../controllers/subnetController.php';
            $alokasiController = new alokasiIpController($conn);

            $id       = (int) ($_POST['id'] ?? 0);
            $unit     = trim($_POST['unit'] ?? '');
            $lokasi   = trim($_POST['lokasi'] ?? '');
            $hostname = trim($_POST['hostname'] ?? '');
            $octet    = (int) ($_POST['host_octet'] ?? 0);

            $subnetRow = (new subnetController($conn))->byCidr(trim($_POST['subnet'] ?? ''));
            $subnet    = $subnetRow['cidr'] ?? '';

            $existing = $id > 0 ? $alokasiController->show($id) : null;
            $status   = $existing['status'] ?? 'online';

            if ($subnet === '' || $unit === '' || $hostname === '' || $octet < 10 || $octet > 254) {
                flash('error', 'Data tidak lengkap / subnet tidak dikenal / oktet IP di luar range (10-254).');
            } else {
                try {
                    if ($id > 0) {
                        $alokasiController->update($id, $subnet, $unit, $lokasi, $hostname, $octet, $status);
                        flash('success', 'Alokasi IP berhasil diperbarui.');
                    } else {
                        $alokasiController->store($subnet, $unit, $lokasi, $hostname, $octet, $status);
                        flash('success', 'Alokasi IP berhasil ditambahkan.');
                    }
                } catch (mysqli_sql_exception $e) {
                    flash('error', 'Gagal menyimpan: IP atau hostname mungkin sudah dipakai.');
                }
            }
        }

        redirect($basePath . '/jaringan');
    });
};
