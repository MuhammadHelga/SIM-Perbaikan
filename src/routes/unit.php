<?php

/**
 * Rute unit & barang (khusus admin).
 */

return function (Router $router, mysqli $conn, string $basePath, AuthService $authService): void {
    // Aksi dinamis (wajib POST + CSRF): /unit/ruangan/hapus/{id}, /unit/barang/hapus/{id}
    $router->any('/unit/{jenis:ruangan|barang}/hapus/{id:\d+}', function (array $params) use ($conn, $basePath) {
        requireRole($basePath, ['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
            flash('error', 'Permintaan tidak valid. Silakan coba lagi.');
            redirect($basePath . '/unit');
        }

        $jenis = $params['jenis'];
        $id    = (int) $params['id'];
        $kolom = $jenis === 'ruangan' ? 'id_ruangan' : 'id_barang';

        $stmt = $conn->prepare("SELECT COUNT(*) AS j FROM laporan_kerusakan WHERE $kolom = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $dipakai = (int) $stmt->get_result()->fetch_assoc()['j'];

        if ($dipakai > 0) {
            flash('error', "Tidak bisa dihapus: masih dipakai oleh {$dipakai} laporan.");
        } else {
            if ($jenis === 'ruangan') {
                require_once __DIR__ . '/../controllers/ruanganController.php';
                (new ruanganController($conn))->destroy($id);
            } else {
                require_once __DIR__ . '/../controllers/barangController.php';
                (new barangController($conn))->destroy($id);
            }
            flash('success', 'Data berhasil dihapus.');
        }

        redirect($basePath . '/unit');
    });

    // Simpan (tambah/edit) ruangan atau barang.
    $handleUnitSimpan = function (string $jenis) use ($conn, $basePath): void {
        requireRole($basePath, ['admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrfValid()) {
                flash('error', 'Sesi tidak valid. Silakan coba lagi.');
                redirect($basePath . '/unit');
            }

            require_once __DIR__ . '/../controllers/ruanganController.php';
            require_once __DIR__ . '/../controllers/barangController.php';

            $controller = $jenis === 'ruangan' ? new ruanganController($conn) : new barangController($conn);
            $label      = $jenis === 'ruangan' ? 'Data unit/ruangan' : 'Data barang';

            $id   = (int) ($_POST['id'] ?? 0);
            $kode = trim($_POST['kode'] ?? '');
            $nama = trim($_POST['nama'] ?? '');

            if ($nama !== '') {
                try {
                    if ($id > 0) {
                        $controller->update($id, $kode, $nama);
                        flash('success', $label . ' berhasil diperbarui.');
                    } else {
                        $controller->store($kode, $nama);
                        flash('success', $label . ' berhasil ditambahkan.');
                    }
                } catch (mysqli_sql_exception $e) {
                    flash('error', 'Gagal menyimpan: kode mungkin sudah dipakai.');
                }
            }
        }

        redirect($basePath . '/unit');
    };

    $router->any('/unit/ruangan/simpan', function () use ($handleUnitSimpan) {
        $handleUnitSimpan('ruangan');
    });

    $router->any('/unit/barang/simpan', function () use ($handleUnitSimpan) {
        $handleUnitSimpan('barang');
    });

    $router->any('/unit', function () use ($conn, $basePath) {
        requireRole($basePath, ['admin']);

        require_once __DIR__ . '/../controllers/ruanganController.php';
        require_once __DIR__ . '/../controllers/barangController.php';

        $ruanganList = (new ruanganController($conn))->index()->fetch_all(MYSQLI_ASSOC);
        $barangList  = (new barangController($conn))->index()->fetch_all(MYSQLI_ASSOC);

        require __DIR__ . '/../views/unit_barang/screens/UnitBarangView.php';
    });
};
