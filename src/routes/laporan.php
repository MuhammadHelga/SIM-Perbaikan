<?php

/**
 * Rute laporan kegiatan & kerusakan.
 */

return function (Router $router, mysqli $conn, string $basePath, AuthService $authService): void {
    // Halaman verifikasi surat (publik, dibuka saat QR discan).
    // Sah hanya bila verify_token cocok — ID saja tidak cukup, jadi data
    // tidak bisa dienumerasi dengan menebak ID.
    $renderVerifikasi = function (int $id, string $token) use ($conn): void {
        require_once __DIR__ . '/../models/LaporanKerusakan.php';
        $model = new LaporanKerusakan($conn);

        $row = $token !== '' ? $model->getByVerifyToken($token) : null;

        if (!$row || ($id > 0 && (int) $row['id'] !== $id)) {
            http_response_code(404);
            header('X-Robots-Tag: noindex');
            require __DIR__ . '/../views/errors/screens/404View.php';
            return;
        }

        header('X-Robots-Tag: noindex');
        require __DIR__ . '/../views/laporan/screens/VerifikasiSuratView.php';
    };

    $router->any('/surat/verifikasi/{id:\d+}', function (array $params) use ($renderVerifikasi) {
        $renderVerifikasi((int) $params['id'], (string) ($_GET['token'] ?? ''));
    });

    $router->any('/surat/verifikasi', function () use ($renderVerifikasi) {
        $renderVerifikasi((int) ($_GET['id'] ?? 0), (string) ($_GET['token'] ?? ''));
    });

    $router->get('/laporan/{id:\d+}/riwayat', function (array $params) use ($conn, $basePath) {
        requireLogin($basePath);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');

        require_once __DIR__ . '/../controllers/laporankerusakanController.php';
        $laporanController = new laporankerusakanController($conn);
        $id = (int) $params['id'];
        try {
            if (!$laporanController->show($id)) {
                http_response_code(404);
                echo json_encode(['error' => 'Laporan tidak ditemukan.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo json_encode($laporanController->getHistory($id), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (Throwable $e) {
            app_log_error($e);
            http_response_code(500);
            echo json_encode(['error' => 'Riwayat laporan gagal dimuat.'], JSON_UNESCAPED_UNICODE);
        }
    });

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
                $conn->begin_transaction();
                try {
                    $changed = $laporanController->kirim($id, date('Y-m-d'));
                    $recorded = $changed && $laporanController->recordHistory(
                        $id,
                        (int) ($_SESSION['user_id'] ?? 0) ?: null,
                        (string) ($_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pengguna'),
                        'Barang dikirim',
                        'Barang dikirim ke vendor/service.'
                    );
                    if (!$recorded) {
                        $conn->rollback();
                        flash('error', 'Laporan tidak dapat dikirim: pastikan statusnya Pending/Proses dan belum pernah dikirim.');
                        break;
                    }
                    $conn->commit();
                    flash('success', 'Barang ditandai sudah dikirim.');
                } catch (Throwable $e) {
                    $conn->rollback();
                    app_log_error($e);
                    flash('error', 'Gagal mencatat pengiriman barang.');
                }
                break;
            case 'terima':
                $conn->begin_transaction();
                try {
                    $changed = $laporanController->terima($id, date('Y-m-d'));
                    $recorded = $changed && $laporanController->recordHistory(
                        $id,
                        (int) ($_SESSION['user_id'] ?? 0) ?: null,
                        (string) ($_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pengguna'),
                        'Barang diterima',
                        'Barang diterima kembali dari vendor/service; status penanganan belum otomatis diubah.'
                    );
                    if (!$recorded) {
                        $conn->rollback();
                        flash('error', 'Barang hanya dapat ditandai diterima setelah statusnya dikirim.');
                        break;
                    }
                    $conn->commit();
                    flash('success', 'Barang ditandai sudah diterima.');
                } catch (Throwable $e) {
                    $conn->rollback();
                    app_log_error($e);
                    flash('error', 'Gagal mencatat penerimaan barang.');
                }
                break;
            case 'hapus':
                $laporanController->destroy($id);
                flash('success', 'Laporan berhasil dihapus.');
                break;
        }

        redirect($basePath . '/laporan');
    });

    // Simpan data penandatangan surat (dikirim dari modal cetak surat).
    // Mode 'kirim' = cetak pertama (sekalian tandai terkirim);
    // mode lain (mis. 'reprint') hanya menyimpan, tidak mengubah status.
    $router->any('/laporan/surat/{id:\d+}', function (array $params) use ($conn, $basePath) {
        requireLogin($basePath);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
            flash('error', 'Permintaan tidak valid. Silakan coba lagi.');
            redirect($basePath . '/laporan');
        }

        require_once __DIR__ . '/../controllers/laporankerusakanController.php';
        $laporanController = new laporankerusakanController($conn);

        $id      = (int) $params['id'];
        $nama    = mb_substr(trim((string) ($_POST['nama_pelapor'] ?? '')), 0, 100);
        $jabatan = mb_substr(trim((string) ($_POST['jabatan_pelapor'] ?? '')), 0, 100);
        $nomor   = mb_substr(trim((string) ($_POST['nomor_surat'] ?? '')), 0, 60);
        $tgl     = trim((string) ($_POST['tgl_surat'] ?? ''));
        $mode    = (string) ($_POST['mode'] ?? 'kirim');

        // Laporan harus ada.
        $laporan = $laporanController->show($id);
        if (!$laporan) {
            flash('error', 'Laporan tidak ditemukan.');
            redirect($basePath . '/laporan');
        }

        if ($mode === 'kirim'
            && (($laporan['kirim_status'] ?? '') !== 'belum'
                || !in_array($laporan['status_penanganan'] ?? '', ['Pending', 'Proses'], true))) {
            flash('error', 'Laporan tidak dapat dikirim: pastikan statusnya Pending/Proses dan belum pernah dikirim.');
            redirect($basePath . '/laporan');
        }

        // Validasi sisi server (bukan hanya di browser): nama & jabatan penandatangan
        // wajib terisi. Ini mencegah data surat kosong walau request dibuat manual.
        if ($nama === '' || $jabatan === '') {
            flash('error', 'Nama dan jabatan penandatangan wajib diisi.');
            redirect($basePath . '/laporan');
        }

        if ($tgl !== '' && !validDateYmd($tgl)) {
            flash('error', 'Tanggal surat tidak valid. Silakan pilih tanggal kalender yang benar.');
            redirect($basePath . '/laporan');
        }
        $tglSurat = $tgl === '' ? null : $tgl;

        $conn->begin_transaction();
        try {
            if (!$laporanController->simpanSurat($id, $nama, $jabatan, $nomor, $tglSurat)) {
                throw new RuntimeException('Gagal menyimpan data surat kerusakan.');
            }
            if (!$laporanController->recordHistory(
                $id,
                (int) ($_SESSION['user_id'] ?? 0) ?: null,
                (string) ($_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pengguna'),
                'Surat kerusakan disimpan',
                'Data penandatangan dan tanggal surat diperbarui.'
            )) {
                throw new RuntimeException('Gagal mencatat perubahan data surat.');
            }

            if ($mode === 'kirim') {
                if (!$laporanController->kirim($id, date('Y-m-d'))) {
                    throw new RuntimeException('Status pengiriman tidak berubah.');
                }
                if (!$laporanController->recordHistory(
                    $id,
                    (int) ($_SESSION['user_id'] ?? 0) ?: null,
                    (string) ($_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pengguna'),
                    'Barang dikirim',
                    'Barang dikirim ke vendor/service.'
                )) {
                    throw new RuntimeException('Gagal mencatat pengiriman barang.');
                }
            }
            $conn->commit();
            flash('success', $mode === 'kirim'
                ? 'Data surat tersimpan dan barang ditandai sudah dikirim.'
                : 'Data surat kerusakan tersimpan.');
        } catch (Throwable $e) {
            $conn->rollback();
            app_log_error($e);
            if ($mode === 'kirim') {
                flash('error', 'Surat atau status pengiriman gagal disimpan. Muat ulang data lalu coba kembali.');
            } else {
                flash('error', 'Gagal menyimpan data surat kerusakan.');
            }
        }

        redirect($basePath . '/laporan');
    });

    $router->any('/laporan', function () use ($conn, $basePath) {
        requireLogin($basePath);

        $periode      = $_GET['periode'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        $search       = $_GET['search'] ?? '';

        // Abaikan periode yang tidak berformat YYYY-MM agar tidak jadi filter aneh.
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $periode) !== 1) {
            $periode = '';
        }

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

    // Cetak rekap: semua baris sesuai filter (tanpa paginasi).
    $router->any('/laporan/cetak', function () use ($conn, $basePath) {
        requireLogin($basePath);

        $periode      = $_GET['periode'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        $search       = $_GET['search'] ?? '';

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $periode) !== 1) {
            $periode = '';
        }

        require_once __DIR__ . '/../services/LaporanService.php';
        $service = new LaporanService($conn);

        $filters = ['periode' => $periode, 'status' => $statusFilter, 'search' => $search];

        $laporanList = $service->getAllFiltered($filters);
        $stats       = $service->getStats($periode);

        require __DIR__ . '/../views/laporan/screens/LaporanCetakView.php';
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

            $transactionStarted = false;
            try {
                $conn->begin_transaction();
                $transactionStarted = true;
                $newId = $laporanController->store(
                    $data['barang_id'],
                    $data['unit_id'],
                    $data['jenis_poli'],
                    $data['tanggal'],
                    $data['waktu_kejadian'],
                    $data['no_seri'],
                    $data['rincian'],
                    $data['uraian'],
                    $data['status'],
                    $data['prioritas'],
                    $_SESSION['user_id'] ?? null
                );
                $newId = (int) $newId;
                if ($newId < 1 || !$laporanController->recordHistory(
                    $newId,
                    (int) ($_SESSION['user_id'] ?? 0) ?: null,
                    (string) ($_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pengguna'),
                    'Laporan dibuat',
                    'Laporan dibuat dengan status ' . $data['status'] . ' dan prioritas ' . $data['prioritas'] . '.'
                        . ($data['waktu_kejadian'] !== null
                            ? ' Waktu kejadian/Laporan diterima: ' . $data['waktu_kejadian'] . '.'
                            : ' Waktu kejadian/Laporan diterima belum dicatat.')
                )) {
                    throw new RuntimeException('Gagal mencatat pembuatan laporan.');
                }
                $conn->commit();
                $transactionStarted = false;

                flash('success', 'Laporan berhasil disimpan.');
            } catch (Throwable $e) {
                if ($transactionStarted) {
                    $conn->rollback();
                }
                app_log_error($e);
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

            $existingJenisPoli = mb_strtoupper(trim((string) ($existing['urusan'] ?? '')), 'UTF-8') === 'POLI'
                ? ($existing['jenis_poli'] ?? null)
                : null;
            [$data, $errors] = validateLaporanInput($conn, $input, $existingJenisPoli);

            if ($errors) {
                flash('error', implode(' ', $errors));
                redirect($basePath . '/laporan');
            }

            $transactionStarted = false;
            try {
                $changes = [];
                $trackedChanges = [
                    'tanggal' => ['Tanggal', $existing['tanggal'] ?? '', $data['tanggal']],
                    'waktu_kejadian' => [
                        'Waktu kejadian/Laporan diterima',
                        $existing['waktu_kejadian'] ?? '',
                        $data['waktu_kejadian'] ?? '',
                    ],
                    'no_seri' => ['Nomor seri', $existing['serial_number'] ?? '', $data['no_seri']],
                    'rincian_kerusakan' => ['Rincian kerusakan', $existing['rincian_kerusakan'] ?? '', $data['rincian']],
                    'uraian_kegiatan' => ['Uraian kegiatan', $existing['uraian_kegiatan'] ?? '', $data['uraian']],
                    'status_penanganan' => ['Status penanganan', $existing['status_penanganan'] ?? '', $data['status']],
                    'prioritas' => ['Prioritas', $existing['prioritas'] ?? '', $data['prioritas']],
                ];
                foreach ($trackedChanges as [$label, $before, $after]) {
                    $before = trim((string) $before);
                    $after = trim((string) $after);
                    if ($before !== $after) {
                        $changes[] = $label . ': ' . ($before === '' ? '(kosong)' : $before)
                            . ' → ' . ($after === '' ? '(kosong)' : $after);
                    }
                }
                if ((int) ($existing['id_barang'] ?? 0) !== $data['barang_id']) {
                    $changes[] = 'Jenis barang diperbarui.';
                }
                if ((int) ($existing['id_ruangan'] ?? 0) !== $data['unit_id']
                    || (string) ($existing['jenis_poli'] ?? '') !== $data['jenis_poli']) {
                    $changes[] = 'Unit/ruangan atau jenis poli diperbarui.';
                }

                $conn->begin_transaction();
                $transactionStarted = true;
                $updated = $laporanController->update(
                    $id,
                    $data['barang_id'],
                    $data['unit_id'],
                    $data['jenis_poli'],
                    $data['tanggal'],
                    $data['waktu_kejadian'],
                    $data['no_seri'],
                    $data['rincian'],
                    $data['uraian'],
                    $data['status'],
                    $data['prioritas']
                );
                if (!$updated || !$laporanController->recordHistory(
                    $id,
                    (int) ($_SESSION['user_id'] ?? 0) ?: null,
                    (string) ($_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pengguna'),
                    'Laporan diperbarui',
                    $changes ? implode('; ', $changes) : 'Laporan disimpan tanpa perubahan nilai.'
                )) {
                    throw new RuntimeException('Gagal menyimpan perubahan atau riwayat laporan.');
                }
                $conn->commit();
                $transactionStarted = false;

                flash('success', 'Laporan berhasil diperbarui.');
            } catch (Throwable $e) {
                if ($transactionStarted) {
                    $conn->rollback();
                }
                app_log_error($e);
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
};
