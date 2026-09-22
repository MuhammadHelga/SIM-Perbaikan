<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

$errorMessage = null;
$oldUsername  = '';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath   = '/SIMKerusakan/SIM-Perbaikan'; // Sesuaikan jika menggunakan sub-folder XAMPP
$path       = str_replace($basePath, '', $requestUri);

switch ($path) {
    // Alur Login (Tampil Form & Eksekusi POST)
    case '/login':
        // Jika sudah login, langsung lempar ke dashboard
        if (!empty($_SESSION['is_logged_in'])) {
            header('Location: ' . $basePath . '/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            // Validasi data login (Dapat disesuaikan dengan koneksi database MySQL/PDO)
            if ($username !== '' && $password !== '') {
                // Simpan status session login
                $_SESSION['is_logged_in'] = true;
                $_SESSION['username']     = $username;

                // Redirect ke Dashboard
                header('Location: ' . $basePath . '/dashboard');
                exit;
            } else {
                $errorMessage = 'Username dan password wajib diisi!';
                $oldUsername  = $username;
            }
        }

        require __DIR__ . '/../src/views/login/screens/LoginView.php';
        break;

    // Alur Logout
    case '/logout':
        session_destroy();
        header('Location: ' . $basePath . '/login');
        exit;

    // Halaman Dashboard Utama
    case '/dashboard':
    case '/':
        // Proteksi halaman: Wajib login
        if (empty($_SESSION['is_logged_in'])) {
            header('Location: ' . $basePath . '/login');
            exit;
        }
        require __DIR__ . '/../src/views/dashboard/screens/DashboardView.php';
        break;

    // Route Lainnya (Tetap Dilindungi Autentikasi)
    case '/laporan':
        if (empty($_SESSION['is_logged_in'])) { header('Location: ' . $basePath . '/login'); exit; }
        require __DIR__ . '/../src/views/laporan/screens/laporanView.php';
        break;

    case '/laporan/tambah':
        if (empty($_SESSION['is_logged_in'])) { header('Location: ' . $basePath . '/login'); exit; }
        require __DIR__ . '/../src/views/laporan/screens/tambahLaporanView.php';
        break;

    case '/ruang':
        if (empty($_SESSION['is_logged_in'])) { header('Location: ' . $basePath . '/login'); exit; }
        require __DIR__ . '/../src/views/ruang/screens/ruangView.php';
        break;

    case '/unit':
        if (empty($_SESSION['is_logged_in'])) { header('Location: ' . $basePath . '/login'); exit; }
        require __DIR__ . '/../src/views/unit_barang/screens/unitBarangView.php';
        break;

    default:
        require __DIR__ . '/../src/views/dashboard/screens/DashboardView.php';
        break;
}