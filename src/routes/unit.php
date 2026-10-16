<?php

/**
 * Rute unit & barang (admin & teknisi).
 */

return function (Router $router, mysqli $conn, string $basePath, AuthService $authService): void {
    // Hitung pemakaian ruangan/barang: dari laporan DAN aset. Aset tidak lagi
    // ikut terhapus otomatis (FK RESTRICT), jadi pemakaiannya dicek di sini juga.
    $pemakaianUnit = function (string $jenis, int $id) use ($conn): array {
        $kolom = $jenis === 'ruangan' ? 'id_ruangan' : 'id_barang';

        $stmt = $conn->prepare("SELECT COUNT(*) AS j FROM laporan_kerusakan WHERE $kolom = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $laporan = (int) $stmt->get_result()->fetch_assoc()['j'];

        $stmt = $conn->prepare("SELECT COUNT(*) AS j FROM aset_barang WHERE $kolom = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $aset = (int) $stmt->get_result()->fetch_assoc()['j'];

        return ['aset' => $aset, 'laporan' => $laporan];
    };

    $pesanPemakaian = function (array $pakai): string {
        $bagian = [];
        if ($pakai['aset'] > 0)    { $bagian[] = "{$pakai['aset']} aset"; }
        if ($pakai['laporan'] > 0) { $bagian[] = "{$pakai['laporan']} laporan"; }
        return implode(' dan ', $bagian);
    };

    // Nonaktifkan ruangan/barang (soft-delete). Hanya bila tidak sedang dipakai
    // aset/laporan, supaya tidak meninggalkan referensi menggantung.
    $router->any('/unit/{jenis:ruangan|barang}/hapus/{id:\d+}', function (array $params) use ($conn, $basePath, $pemakaianUnit, $pesanPemakaian) {
        requireRole($basePath, ['admin', 'teknisi']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
            flash('error', 'Permintaan tidak valid. Silakan coba lagi.');
            redirect($basePath . '/unit');
        }

        $jenis = $params['jenis'];
        $id    = (int) $params['id'];
        $pakai = $pemakaianUnit($jenis, $id);

        if ($pakai['aset'] > 0 || $pakai['laporan'] > 0) {
            flash('error', 'Tidak bisa dinonaktifkan: masih dipakai oleh ' . $pesanPemakaian($pakai) . '.');
        } else {
            try {
                if ($jenis === 'ruangan') {
                    require_once __DIR__ . '/../controllers/ruanganController.php';
                    (new ruanganController($conn))->destroy($id);
                } else {
                    require_once __DIR__ . '/../controllers/barangController.php';
                    (new barangController($conn))->destroy($id);
                }
                flash('success', 'Data berhasil dinonaktifkan.');
            } catch (mysqli_sql_exception $e) {
                flash('error', 'Gagal menonaktifkan: data masih terhubung dengan data lain.');
            }
        }

        redirect($basePath . '/unit');
    });

    // Aktifkan kembali ruangan/barang yang nonaktif.
    $router->any('/unit/{jenis:ruangan|barang}/aktifkan/{id:\d+}', function (array $params) use ($conn, $basePath) {
        requireRole($basePath, ['admin', 'teknisi']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
            flash('error', 'Permintaan tidak valid. Silakan coba lagi.');
            redirect($basePath . '/unit');
        }

        $id = (int) $params['id'];
        if ($params['jenis'] === 'ruangan') {
            require_once __DIR__ . '/../controllers/ruanganController.php';
            (new ruanganController($conn))->restore($id);
        } else {
            require_once __DIR__ . '/../controllers/barangController.php';
            (new barangController($conn))->restore($id);
        }

        flash('success', 'Data diaktifkan kembali.');
        redirect($basePath . '/unit');
    });

    // Hapus permanen: hanya bila tidak dipakai aset/laporan mana pun.
    $router->any('/unit/{jenis:ruangan|barang}/hapus-permanen/{id:\d+}', function (array $params) use ($conn, $basePath, $pemakaianUnit, $pesanPemakaian) {
        requireRole($basePath, ['admin', 'teknisi']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
            flash('error', 'Permintaan tidak valid. Silakan coba lagi.');
            redirect($basePath . '/unit');
        }

        $jenis = $params['jenis'];
        $id    = (int) $params['id'];
        $pakai = $pemakaianUnit($jenis, $id);

        if ($pakai['aset'] > 0 || $pakai['laporan'] > 0) {
            flash('error', 'Tidak bisa dihapus permanen: masih dipakai oleh ' . $pesanPemakaian($pakai) . '.');
            redirect($basePath . '/unit');
        }

        try {
            if ($jenis === 'ruangan') {
                require_once __DIR__ . '/../controllers/ruanganController.php';
                (new ruanganController($conn))->destroyPermanent($id);
            } else {
                require_once __DIR__ . '/../controllers/barangController.php';
                (new barangController($conn))->destroyPermanent($id);
            }
            flash('success', 'Data dihapus permanen.');
        } catch (mysqli_sql_exception $e) {
            flash('error', 'Gagal menghapus permanen: data masih terhubung dengan data lain.');
        }

        redirect($basePath . '/unit');
    });

    // Simpan (tambah/edit) ruangan atau barang.
    $handleUnitSimpan = function (string $jenis) use ($conn, $basePath): void {
        requireRole($basePath, ['admin', 'teknisi']);

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

    // Simpan (tambah/edit) aset barang: unit fisik yang menautkan ruangan & barang.
    $handleAsetSimpan = function () use ($conn, $basePath): void {
        requireRole($basePath, ['admin', 'teknisi']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrfValid()) {
                flash('error', 'Sesi tidak valid. Silakan coba lagi.');
                redirect($basePath . '/unit');
            }

            require_once __DIR__ . '/../controllers/asetBarangController.php';

            $id         = (int) ($_POST['id'] ?? 0);
            $idRuangan  = (int) ($_POST['id_ruangan'] ?? 0);
            $idBarang   = (int) ($_POST['id_barang'] ?? 0);
            $serial     = mb_substr(trim((string) ($_POST['serial_number'] ?? '')), 0, 50);
            $tipe       = trim((string) ($_POST['tipe'] ?? ''));
            $keterangan = trim((string) ($_POST['keterangan'] ?? ''));

            if ($idRuangan <= 0 || $idBarang <= 0) {
                flash('error', 'Ruangan dan jenis barang wajib diisi.');
            } else {
                $serial = $serial === '' ? null : $serial;
                $tipe = $tipe === '' ? null : mb_substr($tipe, 0, 80);
                $keterangan = $keterangan === '' ? null : mb_substr($keterangan, 0, 200);

                try {
                    $asetController = new asetBarangController($conn);
                    if ($id > 0) {
                        $asetController->update($id, $idRuangan, $idBarang, $serial, $tipe, $keterangan);
                        flash('success', 'Aset barang berhasil diperbarui.');
                    } else {
                        $asetController->store($idRuangan, $idBarang, $serial, $tipe, $keterangan);
                        flash('success', 'Aset barang berhasil ditambahkan.');
                    }
                } catch (mysqli_sql_exception $e) {
                    flash('error', 'Gagal menyimpan: serial number mungkin sudah dipakai.');
                }
            }
        }

        redirect($basePath . '/unit');
    };

    $router->any('/unit/aset/simpan', function () use ($handleAsetSimpan) {
        $handleAsetSimpan();
    });

    // Nonaktifkan aset (soft-delete): laporan yang pernah memakainya tetap utuh.
    $router->any('/unit/aset/hapus/{id:\d+}', function (array $params) use ($conn, $basePath) {
        requireRole($basePath, ['admin', 'teknisi']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
            flash('error', 'Permintaan tidak valid. Silakan coba lagi.');
            redirect($basePath . '/unit');
        }

        require_once __DIR__ . '/../controllers/asetBarangController.php';
        (new asetBarangController($conn))->destroy((int) $params['id']);

        flash('success', 'Aset barang dinonaktifkan. Laporan yang pernah memakainya tetap utuh.');
        redirect($basePath . '/unit');
    });

    // Aktifkan kembali aset yang nonaktif.
    $router->any('/unit/aset/aktifkan/{id:\d+}', function (array $params) use ($conn, $basePath) {
        requireRole($basePath, ['admin', 'teknisi']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
            flash('error', 'Permintaan tidak valid. Silakan coba lagi.');
            redirect($basePath . '/unit');
        }

        require_once __DIR__ . '/../controllers/asetBarangController.php';
        (new asetBarangController($conn))->restore((int) $params['id']);

        flash('success', 'Aset barang diaktifkan kembali.');
        redirect($basePath . '/unit');
    });

    // Hapus permanen: hanya bila aset tidak dirujuk laporan mana pun.
    $router->any('/unit/aset/hapus-permanen/{id:\d+}', function (array $params) use ($conn, $basePath) {
        requireRole($basePath, ['admin', 'teknisi']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
            flash('error', 'Permintaan tidak valid. Silakan coba lagi.');
            redirect($basePath . '/unit');
        }

        $id = (int) $params['id'];

        $stmt = $conn->prepare('SELECT COUNT(*) AS j FROM laporan_kerusakan WHERE id_aset = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $dipakai = (int) $stmt->get_result()->fetch_assoc()['j'];

        if ($dipakai > 0) {
            flash('error', "Tidak bisa dihapus permanen: masih dipakai oleh {$dipakai} laporan.");
            redirect($basePath . '/unit');
        }

        require_once __DIR__ . '/../controllers/asetBarangController.php';
        (new asetBarangController($conn))->destroyPermanent($id);

        flash('success', 'Aset barang dihapus permanen.');
        redirect($basePath . '/unit');
    });

    $router->any('/unit', function () use ($conn, $basePath) {
        requireRole($basePath, ['admin', 'teknisi']);

        require_once __DIR__ . '/../controllers/ruanganController.php';
        require_once __DIR__ . '/../controllers/barangController.php';
        require_once __DIR__ . '/../controllers/asetBarangController.php';

        $ruanganList = (new ruanganController($conn))->indexAll()->fetch_all(MYSQLI_ASSOC);
        $barangList  = (new barangController($conn))->indexAll()->fetch_all(MYSQLI_ASSOC);
        $asetList    = (new asetBarangController($conn))->indexAll();

        // Dropdown pada form aset hanya menampilkan ruangan/barang yang aktif.
        $ruanganAktifList = array_values(array_filter(
            $ruanganList,
            static fn(array $r): bool => (int) ($r['aktif'] ?? 1) === 1
        ));
        $barangAktifList = array_values(array_filter(
            $barangList,
            static fn(array $b): bool => (int) ($b['aktif'] ?? 1) === 1
        ));

        require __DIR__ . '/../views/unit_barang/screens/UnitBarangView.php';
    });
};
