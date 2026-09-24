<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/conf.php';
require __DIR__ . '/../config/database.php';

$conn = db();

require_once __DIR__ . '/../src/services/AuthService.php';

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

// ===== Route dinamis: /laporan/kirim/{id}, /laporan/terima/{id}, /laporan/hapus/{id} =====
if (preg_match('#^/laporan/(kirim|terima|hapus)/(\d+)$#', $path, $routeMatch)) {
    requireLogin($basePath);

    require_once __DIR__ . '/../src/controllers/laporankerusakanController.php';
    $laporanController = new laporankerusakanController($conn);

    $id = (int) $routeMatch[2];
    switch ($routeMatch[1]) {
        case 'kirim':
            $laporanController->kirim($id, date('Y-m-d'));
            break;
        case 'terima':
            $laporanController->terima($id, date('Y-m-d'));
            break;
        case 'hapus':
            $laporanController->destroy($id);
            break;
    }

    header('Location: ' . $basePath . '/laporan');
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
            } elseif ((new AuthService($conn))->login($username, $password)) {
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
        (new AuthService($conn))->logout();
        header('Location: ' . $basePath . '/login');
        exit;

    case '/dashboard':
        requireLogin($basePath);

        require __DIR__ . '/../src/views/dashboard/screens/DashboardView.php';
        break;
    case '/':

        if (empty($_SESSION['is_logged_in'])) {
            header('Location: ' . $basePath . '/login');
            exit;
        }

        require __DIR__ . '/../src/views/login/screens/LoginView.php';
        break;

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
            $laporanList = array_values(array_filter($laporanList, fn($r) => $r['hasil'] === $statusFilter));
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

        $stats = [
            'total'   => count($laporanList),
            'pending' => count(array_filter($laporanList, fn($r) => $r['hasil'] === 'pending')),
            'selesai' => count(array_filter($laporanList, fn($r) => $r['hasil'] === 'selesai')),
        ];

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

        require __DIR__ . '/../src/views/ruang/screens/RuangView.php';
        break;

    case '/unit':
        requireLogin($basePath);

        require __DIR__ . '/../src/views/unit_barang/screens/UnitBarangView.php';
        break;

    default:
        http_response_code(404);
        echo '404 - Halaman tidak ditemukan.';
        break;
}
