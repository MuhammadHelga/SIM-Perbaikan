<?php

/**
 * Rute kelola subnet (khusus admin).
 */

return function (Router $router, mysqli $conn, string $basePath, AuthService $authService): void {
    // Aksi dinamis (wajib POST + CSRF): /subnet/hapus/{id}
    $router->any('/subnet/hapus/{id:\d+}', function (array $params) use ($conn, $basePath) {
        requireRole($basePath, ['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
            flash('error', 'Permintaan tidak valid. Silakan coba lagi.');
            redirect($basePath . '/subnet');
        }

        $id = (int) $params['id'];

        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS j FROM alokasi_ip
             WHERE subnet = (SELECT cidr FROM subnet WHERE id = ?)"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $dipakai = (int) $stmt->get_result()->fetch_assoc()['j'];

        if ($dipakai > 0) {
            flash('error', "Tidak bisa dihapus: masih dipakai {$dipakai} alokasi IP.");
        } else {
            require_once __DIR__ . '/../controllers/subnetController.php';
            (new subnetController($conn))->destroy($id);
            flash('success', 'Subnet berhasil dihapus.');
        }

        redirect($basePath . '/subnet');
    });

    $router->any('/subnet/simpan', function () use ($conn, $basePath) {
        requireRole($basePath, ['admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrfValid()) {
                flash('error', 'Sesi tidak valid. Silakan coba lagi.');
                redirect($basePath . '/subnet');
            }

            require_once __DIR__ . '/../controllers/subnetController.php';
            $subnetController = new subnetController($conn);

            $id         = (int) ($_POST['id'] ?? 0);
            $cidr       = trim($_POST['cidr'] ?? '');
            $prefix     = trim($_POST['prefix'] ?? '');
            $gateway    = trim($_POST['gateway'] ?? '');
            $mask       = trim($_POST['mask'] ?? '');
            $keterangan = trim($_POST['keterangan'] ?? '');

            if ($cidr === '' || $prefix === '') {
                flash('error', 'CIDR dan Prefix wajib diisi.');
            } else {
                try {
                    if ($id > 0) {
                        $subnetController->update($id, $cidr, $prefix, $gateway, $mask, $keterangan);
                        flash('success', 'Subnet berhasil diperbarui.');
                    } else {
                        $subnetController->store($cidr, $prefix, $gateway, $mask, $keterangan);
                        flash('success', 'Subnet berhasil ditambahkan.');
                    }
                } catch (mysqli_sql_exception $e) {
                    flash('error', 'Gagal menyimpan: CIDR mungkin sudah dipakai.');
                }
            }
        }

        redirect($basePath . '/subnet');
    });

    $router->any('/subnet', function () use ($conn, $basePath) {
        requireRole($basePath, ['admin']);

        require_once __DIR__ . '/../controllers/subnetController.php';
        require_once __DIR__ . '/../controllers/alokasiIpController.php';

        $subnetList   = (new subnetController($conn))->index();
        $alokasiCount = count((new alokasiIpController($conn))->index());

        require __DIR__ . '/../views/subnet/screens/SubnetView.php';
    });
};
