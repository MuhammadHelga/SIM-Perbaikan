<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/conf.php'; 

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

switch ($path) {
    case '/login':

        if (!empty($_SESSION['is_logged_in'])) {
            header('Location: ' . $basePath . '/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if ($username !== '' && $password !== '') {

                $_SESSION['is_logged_in'] = true;
                $_SESSION['username']     = $username;

                header('Location: ' . $basePath . '/dashboard');
                exit;
            } else {
                $errorMessage = 'Username dan password wajib diisi!';
                $oldUsername  = $username;
            }
        }

        require __DIR__ . '/../src/views/login/screens/LoginView.php';
        break;

    case '/logout':
        session_destroy();
        header('Location: ' . $basePath . '/login');
        exit;

    case '/dashboard':
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

        $laporanList = [
            ['id'=>1,  'tanggal'=>'01 Sep 2026', 'urusan'=>'Verifikasi',   'barang'=>'Aplikasi',  'serial_number'=>'-',        'kerusakan'=>'Permintaan update Inacbgs/Eklaim',                         'uraian'=>'Update aplikasi Inacbgs sesuai versi terbaru dari Kemenkes.', 'hasil'=>'selesai', 'kirim_status'=>'belum'],
            ['id'=>2,  'tanggal'=>'02 Sep 2026', 'urusan'=>'Fisioterapi',  'barang'=>'Printer',    'serial_number'=>'PRN-2214', 'kerusakan'=>'Permintaan install printer di laptop bu Rina',             'uraian'=>'Instalasi driver printer Epson L120, sudah bisa print.',      'hasil'=>'selesai', 'kirim_status'=>'belum'],
            ['id'=>3,  'tanggal'=>'03 Sep 2026', 'urusan'=>'Parkir',       'barang'=>'Komputer',   'serial_number'=>'PC-0087',  'kerusakan'=>'Komputer pak Yusron sangat lemot',                         'uraian'=>'Disk cleanup, uninstall aplikasi tidak terpakai, tambah RAM 4GB.', 'hasil'=>'selesai', 'kirim_status'=>'belum'],
            ['id'=>4,  'tanggal'=>'04 Sep 2026', 'urusan'=>'Keuangan',     'barang'=>'Jaringan',   'serial_number'=>'-',        'kerusakan'=>'Permintaan cek data bridging parkir dgn Dipenda',          'uraian'=>'Koordinasi dengan vendor, masih menunggu respons pihak Dipenda.', 'hasil'=>'pending', 'kirim_status'=>'belum'],
            ['id'=>5,  'tanggal'=>'05 Sep 2026', 'urusan'=>'Poli',         'barang'=>'Jaringan',   'serial_number'=>'-',        'kerusakan'=>'Komputer poli KIA jaringan tidak bisa',                     'uraian'=>'Cek kabel LAN & switch, ganti kabel yang rusak.',              'hasil'=>'selesai', 'kirim_status'=>'belum'],
            ['id'=>6,  'tanggal'=>'06 Sep 2026', 'urusan'=>'Perinatologi', 'barang'=>'Printer',    'serial_number'=>'PRN-1187', 'kerusakan'=>'Perin tidak bisa print file regis',                        'uraian'=>'Install ulang driver printer, sudah normal.',                 'hasil'=>'selesai', 'kirim_status'=>'belum'],
            ['id'=>7,  'tanggal'=>'07 Sep 2026', 'urusan'=>'IT',           'barang'=>'Aplikasi',   'serial_number'=>'-',        'kerusakan'=>'Pengiriman perbaikan kualitas data bridging antrol BPJS',  'uraian'=>'Masih koordinasi dengan tim BPJS Center pusat.',              'hasil'=>'pending', 'kirim_status'=>'belum'],
            ['id'=>8,  'tanggal'=>'08 Sep 2026', 'urusan'=>'PPK1',         'barang'=>'CPU',        'serial_number'=>'SMB-0231', 'kerusakan'=>'CPU merk Simbadda keluar api dari area port USB depan',    'uraian'=>'Sudah dikirim ke vendor service luar (Jember) untuk pengecekan lanjut.', 'hasil'=>'pending', 'kirim_status'=>'dikirim', 'tgl_kirim'=>'09 Sep 2026'],
            ['id'=>9,  'tanggal'=>'09 Sep 2026', 'urusan'=>'Rehabmedik',   'barang'=>'Printer',    'serial_number'=>'PRN-0765', 'kerusakan'=>'Printer pendaftaran rehabmedik error',                     'uraian'=>'Cleaning head printer & ganti cartridge, sudah normal.',      'hasil'=>'selesai', 'kirim_status'=>'belum'],
            ['id'=>10, 'tanggal'=>'10 Sep 2026', 'urusan'=>'Radiologi',    'barang'=>'Monitor',    'serial_number'=>'MON-3321', 'kerusakan'=>'Monitor viewer radiologi bergaris',                        'uraian'=>'Dikirim ke vendor untuk pengecekan panel LCD.',               'hasil'=>'pending', 'kirim_status'=>'dikirim', 'tgl_kirim'=>'11 Sep 2026'],
            ['id'=>11, 'tanggal'=>'11 Sep 2026', 'urusan'=>'Admisi',       'barang'=>'Komputer',   'serial_number'=>'PC-0045',  'kerusakan'=>'Komputer admisi restart sendiri',                          'uraian'=>'Sudah dikirim ke service center, sekarang sudah kembali & diterima.', 'hasil'=>'selesai', 'kirim_status'=>'diterima', 'tgl_terima'=>'13 Sep 2026'],
            ['id'=>12, 'tanggal'=>'12 Sep 2026', 'urusan'=>'BPJS Center',  'barang'=>'Aplikasi',   'serial_number'=>'-',        'kerusakan'=>'Aplikasi SEP tidak bisa cetak',                            'uraian'=>'Update Adobe Reader & setting default printer, sudah normal.', 'hasil'=>'selesai', 'kirim_status'=>'belum'],
            ['id'=>13, 'tanggal'=>'13 Sep 2026', 'urusan'=>'Kasir',        'barang'=>'Printer',    'serial_number'=>'PRN-4432', 'kerusakan'=>'Printer struk kasir tidak keluar kertas',                  'uraian'=>'Sensor kertas kotor, sudah dibersihkan dan dites ulang.',      'hasil'=>'selesai', 'kirim_status'=>'belum'],
            ['id'=>14, 'tanggal'=>'14 Sep 2026', 'urusan'=>'Laboratorium', 'barang'=>'Jaringan',   'serial_number'=>'-',        'kerusakan'=>'Koneksi LIS ke alat lab terputus-putus',                   'uraian'=>'Sedang dijadwalkan penggantian kabel fiber, menunggu jadwal teknisi.', 'hasil'=>'pending', 'kirim_status'=>'belum'],
            ['id'=>15, 'tanggal'=>'15 Sep 2026', 'urusan'=>'Server',       'barang'=>'Server',     'serial_number'=>'SRV-01',   'kerusakan'=>'Server SIMRS beberapa kali down mendadak',                 'uraian'=>'Fan pendingin diganti, monitoring suhu masih berjalan.',       'hasil'=>'selesai', 'kirim_status'=>'belum'],
        ];

        if ($statusFilter !== '') {
            $laporanList = array_values(array_filter($laporanList, fn($r) => $r['hasil'] === $statusFilter));
        }

        if ($search !== '') {
            $keyword = mb_strtolower($search);
            $laporanList = array_values(array_filter($laporanList, function ($r) use ($keyword) {
                return str_contains(mb_strtolower($r['urusan']), $keyword)
                    || str_contains(mb_strtolower($r['barang']), $keyword)
                    || str_contains(mb_strtolower($r['kerusakan']), $keyword);
            }));
        }

        $stats = [
            'total'   => count($laporanList),
            'pending' => count(array_filter($laporanList, fn($r) => $r['hasil'] === 'pending')),
            'selesai' => count(array_filter($laporanList, fn($r) => $r['hasil'] === 'selesai')),
        ];

        require __DIR__ . '/../src/views/laporan/screens/LaporanView.php';
        break;

    case '/laporan/tambah':
        requireLogin($basePath);

        require __DIR__ . '/../src/views/laporan/screens/TambahLaporanView.php';
        break;

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
