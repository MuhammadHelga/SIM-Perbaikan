<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/conf.php';
require __DIR__ . '/../config/database.php';

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

            if ($username === '' || $password === '') {
                $errorMessage = 'Username dan password wajib diisi!';
                $oldUsername  = $username;
            } elseif ($authService->login($username, $password, !empty($_POST['remember']))) {
                header('Location: ' . $basePath . '/dashboard');
                exit;
            } else {
                $errorMessage = 'Username atau password salah!';
                $oldUsername  = $username;
            }
        }

        require __DIR__ . '/../src/views/login/screens/LoginView.php';
        break;

    case '/logout':
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

    default:
        http_response_code(404);
        require __DIR__ . '/../src/views/errors/screens/404View.php';
        break;
}
