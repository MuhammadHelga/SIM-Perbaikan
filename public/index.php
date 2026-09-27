<?php
session_start();

// ===== Header keamanan (via PHP, tanpa perlu mod_headers) =====
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/conf.php';
require __DIR__ . '/../config/database.php';

// ===== CSRF token per session =====
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function csrfToken(): string
{
    return $_SESSION['csrf'] ?? '';
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrfValid(): bool
{
    return isset($_POST['csrf'])
        && is_string($_POST['csrf'])
        && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
}

$conn = db();

require_once __DIR__ . '/../src/services/AuthService.php';
$authService = new AuthService($conn);
$authService->loginByCookie();

$basePath = BASE_URL;

$errorMessage = null;
$oldUsername  = '';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path       = str_replace($basePath, '', $requestUri);

function requireLogin(string $basePath): void
{
    if (empty($_SESSION['is_logged_in'])) {
        header('Location: ' . $basePath . '/login');
        exit;
    }
}

function requireRole(string $basePath, array $roles): void
{
    requireLogin($basePath);

    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        $_SESSION['flash'] = ['type' => 'error', 'text' => 'Akses ditolak untuk role Anda.'];
        header('Location: ' . $basePath . '/dashboard');
        exit;
    }
}

// ===== Route dinamis: /laporan/kirim/{id}, /laporan/terima/{id}, /laporan/hapus/{id} =====
if (preg_match('#^/laporan/(kirim|terima|hapus)/(\d+)$#', $path, $routeMatch)) {
    requireLogin($basePath);

    if ($routeMatch[1] === 'hapus') {
        requireRole($basePath, ['admin']);
    }

    require_once __DIR__ . '/../src/controllers/laporankerusakanController.php';
    $laporanController = new laporankerusakanController($conn);

    $id = (int) $routeMatch[2];
    switch ($routeMatch[1]) {
        case 'kirim':
            $laporanController->kirim($id, date('Y-m-d'));
            $_SESSION['flash'] = ['type' => 'success', 'text' => 'Barang ditandai sudah dikirim.'];
            break;
        case 'terima':
            $laporanController->terima($id, date('Y-m-d'));
            $_SESSION['flash'] = ['type' => 'success', 'text' => 'Barang ditandai sudah diterima.'];
            break;
        case 'hapus':
            $laporanController->destroy($id);
            $_SESSION['flash'] = ['type' => 'success', 'text' => 'Laporan berhasil dihapus.'];
            break;
    }

    header('Location: ' . $basePath . '/laporan');
    exit;
}

// ===== Route dinamis: /unit/ruangan/hapus/{id}, /unit/barang/hapus/{id} =====
if (preg_match('#^/unit/(ruangan|barang)/hapus/(\d+)$#', $path, $unitMatch)) {
    requireRole($basePath, ['admin']);

    $jenis = $unitMatch[1];
    $id    = (int) $unitMatch[2];
    $kolom = $jenis === 'ruangan' ? 'id_ruangan' : 'id_barang';

    $stmt = $conn->prepare("SELECT COUNT(*) AS j FROM laporankerusakan WHERE $kolom = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $dipakai = (int) $stmt->get_result()->fetch_assoc()['j'];

    if ($dipakai > 0) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'text' => "Tidak bisa dihapus: masih dipakai oleh {$dipakai} laporan.",
        ];
    } else {
        if ($jenis === 'ruangan') {
            require_once __DIR__ . '/../src/controllers/ruanganController.php';
            (new ruanganController($conn))->destroy($id);
        } else {
            require_once __DIR__ . '/../src/controllers/barangController.php';
            (new barangController($conn))->destroy($id);
        }
        $_SESSION['flash'] = ['type' => 'success', 'text' => 'Data berhasil dihapus.'];
    }

    header('Location: ' . $basePath . '/unit');
    exit;
}

switch ($path) {
    case '/login':

        if (!empty($_SESSION['is_logged_in'])) {
            header('Location: ' . $basePath . '/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $now      = time();

            if (!csrfValid()) {
                $errorMessage = 'Sesi tidak valid. Silakan coba lagi.';
                $oldUsername  = $username;
            } elseif (($_SESSION['login_lock_until'] ?? 0) > $now) {
                $sisa = (int) ($_SESSION['login_lock_until'] - $now);
                $errorMessage = "Terlalu banyak percobaan. Coba lagi dalam {$sisa} detik.";
                $oldUsername  = $username;
            } elseif ($username === '' || $password === '') {
                $errorMessage = 'Username dan password wajib diisi!';
                $oldUsername  = $username;
            } elseif ($authService->login($username, $password, !empty($_POST['remember']))) {
                unset($_SESSION['login_fail'], $_SESSION['login_lock_until']);
                header('Location: ' . $basePath . '/dashboard');
                exit;
            } else {
                $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
                if ($_SESSION['login_fail'] >= 5) {
                    $_SESSION['login_lock_until'] = $now + 60;
                    $_SESSION['login_fail'] = 0;
                }
                $errorMessage = 'Username atau password salah!';
                $oldUsername  = $username;
            }
        }

        require __DIR__ . '/../src/views/login/screens/LoginView.php';
        break;

    case '/logout':
        if (!csrfValid()) {
            $_SESSION['flash'] = ['type' => 'error', 'text' => 'Sesi tidak valid. Silakan coba lagi.'];
            header('Location: ' . $basePath . '/dashboard');
            exit;
        }
        $authService->logout();
        header('Location: ' . $basePath . '/login');
        exit;

    case '/dashboard':
        requireLogin($basePath);

        require_once __DIR__ . '/../src/services/DashboardService.php';
        $dashboard = (new DashboardService($conn))->getData((int) date('Y'));

        require __DIR__ . '/../src/views/dashboard/screens/DashboardView.php';
        break;
    case '/':

        if (empty($_SESSION['is_logged_in'])) {
            header('Location: ' . $basePath . '/login');
            exit;
        }

        header('Location: ' . $basePath . '/dashboard');
        exit;

    case '/laporan':
        requireLogin($basePath);

        $statusFilter = $_GET['status'] ?? '';
        $search       = $_GET['search'] ?? '';
        $periode      = $_GET['periode'] ?? '';

        require_once __DIR__ . '/../src/controllers/laporankerusakanController.php';
        $laporanController = new laporankerusakanController($conn);

        $laporanList = $laporanController->index()->fetch_all(MYSQLI_ASSOC);
        $ruanganList = $laporanController->getRuangan()->fetch_all(MYSQLI_ASSOC);
        $barangList  = $laporanController->getBarang()->fetch_all(MYSQLI_ASSOC);

        if ($periode !== '') {
            $laporanList = array_values(array_filter(
                $laporanList,
                fn($r) => substr((string)$r['tanggal'], 0, 7) === $periode
            ));
        }

        if ($statusFilter !== '') {
            $laporanList = array_values(array_filter($laporanList, fn($r) => $r['status_penanganan'] === $statusFilter));
        }

        if ($search !== '') {
            $keyword = mb_strtolower($search);
            $laporanList = array_values(array_filter($laporanList, function ($r) use ($keyword) {
                return str_contains(mb_strtolower($r['urusan']), $keyword)
                    || str_contains(mb_strtolower($r['barang']), $keyword)
                    || str_contains(mb_strtolower($r['kerusakan']), $keyword)
                    || str_contains(mb_strtolower((string)($r['serial_number'] ?? '')), $keyword);
            }));
        }

        $perPage = (int)($_GET['per_page'] ?? 25);
        if (!in_array($perPage, [25, 50, 100, 200], true)) {
            $perPage = 25;
        }

        $stats = [
            'total'   => count($laporanList),
            'pending' => count(array_filter($laporanList, fn($r) => $r['hasil'] === 'pending')),
            'selesai' => count(array_filter($laporanList, fn($r) => $r['hasil'] === 'selesai')),
        ];

        // Batasi jumlah baris yang ditampilkan sesuai pilihan "Menampilkan ... Laporan"
        $laporanList = array_slice($laporanList, 0, $perPage);

        require __DIR__ . '/../src/views/laporan/screens/LaporanView.php';
        break;

    case '/laporan/simpan':
        requireLogin($basePath);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrfValid()) {
                $_SESSION['flash'] = ['type' => 'error', 'text' => 'Sesi tidak valid. Silakan coba lagi.'];
                header('Location: ' . $basePath . '/laporan');
                exit;
            }

            require_once __DIR__ . '/../src/controllers/laporankerusakanController.php';
            $laporanController = new laporankerusakanController($conn);

            $laporanController->store(
                (int)($_POST['barang_id'] ?? 0),
                (int)($_POST['unit_id'] ?? 0),
                $_POST['tanggal'] ?? date('Y-m-d'),
                trim($_POST['no_seri'] ?? ''),
                trim($_POST['rincian_kerusakan'] ?? ''),
                trim($_POST['uraian_kegiatan'] ?? ''),
                $_POST['status'] ?? 'Pending',
                $_POST['prioritas'] ?? 'Sedang',
                $_SESSION['user_id'] ?? null
            );

            $_SESSION['flash'] = ['type' => 'success', 'text' => 'Laporan berhasil disimpan.'];
        }

        header('Location: ' . $basePath . '/laporan');
        exit;

    case '/laporan/update':
        requireLogin($basePath);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrfValid()) {
                $_SESSION['flash'] = ['type' => 'error', 'text' => 'Sesi tidak valid. Silakan coba lagi.'];
                header('Location: ' . $basePath . '/laporan');
                exit;
            }

            require_once __DIR__ . '/../src/controllers/laporankerusakanController.php';
            $laporanController = new laporankerusakanController($conn);

            $id = (int)($_POST['id'] ?? 0);
            $existing = $laporanController->show($id);

            if ($existing) {
                $laporanController->update(
                    $id,
                    (int)($_POST['barang_id'] ?? 0),
                    (int)($_POST['unit_id'] ?? 0),
                    $_POST['tanggal'] ?? date('Y-m-d'),
                    trim($_POST['no_seri'] ?? ''),
                    trim($_POST['rincian_kerusakan'] ?? ''),
                    trim($_POST['uraian_kegiatan'] ?? ''),
                    $_POST['status'] ?? 'Pending',
                    $_POST['prioritas'] ?? $existing['prioritas'] ?? 'Sedang'
                );

                $_SESSION['flash'] = ['type' => 'success', 'text' => 'Laporan berhasil diperbarui.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'text' => 'Laporan tidak ditemukan.'];
            }
        }

        header('Location: ' . $basePath . '/laporan');
        exit;

    case '/laporan/tambah':
        // Penambahan laporan dilakukan lewat modal di halaman /laporan
        requireLogin($basePath);
        header('Location: ' . $basePath . '/laporan');
        exit;

    case '/ruang':
        requireLogin($basePath);

        require_once __DIR__ . '/../src/services/RuangService.php';

        $ruangService = new RuangService($conn);

        $sort      = ($_GET['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $q         = trim($_GET['q'] ?? '');
        $tahunList = $ruangService->getTahunTersedia();
        $tahun     = (int) ($_GET['tahun'] ?? date('Y'));

        if (!in_array($tahun, $tahunList, true)) {
            $tahun = $tahunList[0] ?? (int) date('Y');
        }

        $ruangData = $ruangService->getData($tahun, $q, $sort);

        $rows          = $ruangData['rows'];
        $totalPerBulan = $ruangData['totalPerBulan'];
        $topUnit       = $ruangData['topUnit'];
        $peakMonths    = $ruangData['peakMonths'];
        $peakTotal     = $ruangData['peakTotal'];
        $jumlahRuangan = $ruangData['jumlahRuangan'];

        require __DIR__ . '/../src/views/ruang/screens/RuangView.php';
        break;

    case '/unit/ruangan/simpan':
        requireRole($basePath, ['admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrfValid()) {
                $_SESSION['flash'] = ['type' => 'error', 'text' => 'Sesi tidak valid. Silakan coba lagi.'];
                header('Location: ' . $basePath . '/unit');
                exit;
            }

            require_once __DIR__ . '/../src/controllers/ruanganController.php';
            $controller = new ruanganController($conn);

            $id   = (int) ($_POST['id'] ?? 0);
            $kode = trim($_POST['kode'] ?? '');
            $nama = trim($_POST['nama'] ?? '');

            if ($nama !== '') {
                try {
                    if ($id > 0) {
                        $controller->update($id, $kode, $nama);
                        $_SESSION['flash'] = ['type' => 'success', 'text' => 'Data unit/ruangan berhasil diperbarui.'];
                    } else {
                        $controller->store($kode, $nama);
                        $_SESSION['flash'] = ['type' => 'success', 'text' => 'Data unit/ruangan berhasil ditambahkan.'];
                    }
                } catch (mysqli_sql_exception $e) {
                    $_SESSION['flash'] = ['type' => 'error', 'text' => 'Gagal menyimpan: kode mungkin sudah dipakai.'];
                }
            }
        }

        header('Location: ' . $basePath . '/unit');
        exit;

    case '/unit/barang/simpan':
        requireRole($basePath, ['admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrfValid()) {
                $_SESSION['flash'] = ['type' => 'error', 'text' => 'Sesi tidak valid. Silakan coba lagi.'];
                header('Location: ' . $basePath . '/unit');
                exit;
            }

            require_once __DIR__ . '/../src/controllers/barangController.php';
            $controller = new barangController($conn);

            $id   = (int) ($_POST['id'] ?? 0);
            $kode = trim($_POST['kode'] ?? '');
            $nama = trim($_POST['nama'] ?? '');

            if ($nama !== '') {
                try {
                    if ($id > 0) {
                        $controller->update($id, $kode, $nama);
                        $_SESSION['flash'] = ['type' => 'success', 'text' => 'Data barang berhasil diperbarui.'];
                    } else {
                        $controller->store($kode, $nama);
                        $_SESSION['flash'] = ['type' => 'success', 'text' => 'Data barang berhasil ditambahkan.'];
                    }
                } catch (mysqli_sql_exception $e) {
                    $_SESSION['flash'] = ['type' => 'error', 'text' => 'Gagal menyimpan: kode mungkin sudah dipakai.'];
                }
            }
        }

        header('Location: ' . $basePath . '/unit');
        exit;

    case '/unit':
        requireRole($basePath, ['admin']);

        require_once __DIR__ . '/../src/controllers/ruanganController.php';
        require_once __DIR__ . '/../src/controllers/barangController.php';

        $ruanganController = new ruanganController($conn);
        $barangController  = new barangController($conn);

        $ruanganList = $ruanganController->index()->fetch_all(MYSQLI_ASSOC);
        $barangList  = $barangController->index()->fetch_all(MYSQLI_ASSOC);

        require __DIR__ . '/../src/views/unit_barang/screens/UnitBarangView.php';
        break;
    
    case '/jaringan':
        requireLogin($basePath);

        $subnet = ['gateway' => '192.100.99.1', 'mask' => '255.255.255.0', 'prefix' => '192.100.99'];

        // TODO: ganti dengan query asli ke database (tabel komputer/alokasi_ip)
        $komputerListAll = [
            ['id'=>1,'unit_id'=>1,'unit'=>'Loket Admisi 1 (Rawat Inap)','lokasi'=>'Gedung A - Lantai 1','hostname'=>'PC-ADMISI-01','host_octet'=>18,'status'=>'online','mac'=>'D4:5D:64:A2:18:01','interface'=>'LAN Port RJ-45 (Gigabit)','port_switch'=>'Switch Admisi - Port G0/03','catatan'=>'Meja Admisi 01'],
            ['id'=>2,'unit_id'=>2,'unit'=>'BPJS Center Loket 2 (SEP)','lokasi'=>'Gedung A - Lantai 1','hostname'=>'PC-BPJS-02','host_octet'=>15,'status'=>'online','mac'=>'D4:5D:64:A2:18:02','interface'=>'LAN Port RJ-45 (Gigabit)','port_switch'=>'Switch Admisi - Port G0/05','catatan'=>'Loket BPJS 2'],
            ['id'=>3,'unit_id'=>3,'unit'=>'Laboratorium Patologi Klinik','lokasi'=>'Gedung B - Ruang Lab 02','hostname'=>'PC-LAB-PAT-01','host_octet'=>22,'status'=>'online','mac'=>'D4:5D:64:A2:18:03','interface'=>'LAN Port RJ-45 (Gigabit)','port_switch'=>'Switch Lab - Port G0/02','catatan'=>''],
            ['id'=>4,'unit_id'=>4,'unit'=>'Poli Jantung & Pembuluh Darah','lokasi'=>'Klinik Spesialis - Poli 11','hostname'=>'PC-POLI-JTG','host_octet'=>34,'status'=>'online','mac'=>'D4:5D:64:A2:18:04','interface'=>'LAN Port RJ-45 (Gigabit)','port_switch'=>'Switch Poli Lt.2 - Port G0/11','catatan'=>''],
            ['id'=>5,'unit_id'=>5,'unit'=>'Farmasi Rawat Jalan (Depo 1)','lokasi'=>'Instalasi Farmasi Sentral','hostname'=>'PC-FARM-R2-03','host_octet'=>45,'status'=>'online','mac'=>'D4:5D:64:A2:18:05','interface'=>'LAN Port RJ-45 (Gigabit)','port_switch'=>'Switch Farmasi - Port G0/03','catatan'=>''],
            ['id'=>6,'unit_id'=>6,'unit'=>'Kasir Pembayaran Utama','lokasi'=>'Gedung A - Loket Finansial','hostname'=>'PC-KASIR-01','host_octet'=>50,'status'=>'online','mac'=>'D4:5D:64:A2:18:06','interface'=>'LAN Port RJ-45 (Gigabit)','port_switch'=>'Switch Kasir - Port G0/01','catatan'=>''],
            ['id'=>7,'unit_id'=>7,'unit'=>'Radiologi - Ruang USG 2','lokasi'=>'Instalasi Radiologi PACS','hostname'=>'PC-RAD-02','host_octet'=>65,'status'=>'offline','mac'=>'D4:5D:64:A2:18:07','interface'=>'LAN Port RJ-45 (Gigabit)','port_switch'=>'Switch Radiologi - Port G0/02','catatan'=>'Kabel putus, menunggu perbaikan'],
            ['id'=>8,'unit_id'=>8,'unit'=>'Rekam Medis (Filing & Scan)','lokasi'=>'Gedung Penunjang - RM 01','hostname'=>'PC-RM-04','host_octet'=>72,'status'=>'online','mac'=>'D4:5D:64:A2:18:08','interface'=>'LAN Port RJ-45 (Gigabit)','port_switch'=>'Switch RM - Port G0/04','catatan'=>''],
            ['id'=>9,'unit_id'=>9,'unit'=>'IGD Triase & Resusitasi','lokasi'=>'Instalasi Gawat Darurat','hostname'=>'PC-IGD-01','host_octet'=>88,'status'=>'online','mac'=>'D4:5D:64:A2:18:09','interface'=>'LAN Port RJ-45 (Gigabit)','port_switch'=>'Switch IGD - Port G0/01','catatan'=>''],
            ['id'=>10,'unit_id'=>10,'unit'=>'Poli Anak & Tumbuh Kembang','lokasi'=>'Gedung B Lt. 2','hostname'=>'PC-POLI-ANAK','host_octet'=>95,'status'=>'online','mac'=>'D4:5D:64:A2:18:95','interface'=>'LAN Port RJ-45 (Gigabit)','port_switch'=>'Switch Poli Lt.2 - Port G0/19','catatan'=>'Meja Pendaftaran Poli Anak 01'],
        ];

        $unitOptions = array_values(array_unique(array_column($komputerListAll, 'unit')));
        $unitList = array_map(fn($r) => ['id' => $r['unit_id'], 'label' => $r['hostname'] . ' — ' . $r['unit'] . ' (' . $r['lokasi'] . ')'], $komputerListAll);

        $search       = $_GET['search'] ?? '';
        $filterUnit   = $_GET['unit'] ?? '';
        $filterStatus = $_GET['status'] ?? '';
        $page         = max(1, (int)($_GET['page'] ?? 1));
        $perPage      = 9;

        $filtered = array_values(array_filter($komputerListAll, function ($r) use ($search, $filterUnit, $filterStatus) {
            if ($filterUnit !== '' && $r['unit'] !== $filterUnit) return false;
            if ($filterStatus !== '' && $r['status'] !== $filterStatus) return false;
            if ($search !== '') {
                $keyword = mb_strtolower($search);
                $haystack = mb_strtolower($r['unit'] . ' ' . $r['hostname'] . ' ' . $r['host_octet']);
                if (!str_contains($haystack, $keyword)) return false;
            }
            return true;
        }));

        $totalRows    = count($filtered);
        $komputerList = array_slice($filtered, ($page - 1) * $perPage, $perPage);

        // Statistik & peta okupansi dihitung dari SELURUH data (bukan cuma yang terfilter/halaman aktif)
        $occupancyMap = [];
        foreach ($komputerListAll as $r) {
            $occupancyMap[(int)$r['host_octet']] = $r['status'];
        }
        $terisi   = count($komputerListAll);
        $offline  = count(array_filter($komputerListAll, fn($r) => $r['status'] === 'offline'));
        $online   = $terisi - $offline;
        $kosong   = 245 - $terisi; // range host valid 10-254 = 245 alamat

        $occupancy = [
            'core'            => 9,
            'terisi'          => $terisi,
            'kosong'          => $kosong,
            'tersedia_persen' => round(($kosong / 245) * 100, 1),
            'map'             => $occupancyMap,
        ];

        $stats = [
            'total_unit'     => $terisi,
            'ip_terpakai'    => $terisi,
            'host_kosong'    => $kosong,
            'uptime_percent' => $terisi > 0 ? round(($online / $terisi) * 100, 1) : 100,
            'online'         => $online,
            'offline'        => $offline,
        ];

        require __DIR__ . '/../src/views/jaringan/screens/JaringanView.php';
        break;

    default:
        http_response_code(404);
        require __DIR__ . '/../src/views/errors/screens/404View.php';
        break;
}
