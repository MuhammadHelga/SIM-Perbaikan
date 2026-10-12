<?php

/**
 * Fungsi bantu global: CSRF, validasi input, dan penjaga akses.
 * Di-require dari public/index.php setelah session & config siap.
 */

// ===== CSRF =====

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

function poliJenisOptions(): array
{
    return [
        'Poli Paru',
        'Poli Penyakit Dalam',
        'Poli Obgyn Mata',
        'Poli Jiwa',
        'Poli Andrologi',
        'Poli Penyakit Dalam Dr. Afina',
        'Poli DOTS',
        'Poli Anak',
        'Poli Jantung',
        'Poli Syaraf',
        'Poli KIA',
        'Poli KIA Belakang',
        'Poli Bedah Umum',
        'Poli Gigi',
        'Poli Gizi',
        'Poli Dalam',
        'Poli Orthopedi',
        'Poli Bedah Saraf',
        'Lainnya',
    ];
}

// ===== Flash & redirect =====

function flash(string $type, string $text): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

// ===== Penjaga akses =====

function requireLogin(string $basePath): void
{
    if (empty($_SESSION['is_logged_in'])) {
        redirect($basePath . '/login');
    }
}

function requireRole(string $basePath, array $roles): void
{
    requireLogin($basePath);

    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        flash('error', 'Akses ditolak untuk role Anda.');
        redirect($basePath . '/dashboard');
    }
}

// ===== Validasi input (server-side, jangan percaya dropdown/HTML) =====

function validDateYmd($value): bool
{
    if (!is_string($value) || $value === '') {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);

    return $date !== false && $date->format('Y-m-d') === $value;
}

function parseLocalDateTime(?string $value)
{
    if ($value === null || $value === '') {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value) !== 1) {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d\TH:i', $value);
    if ($date === false || $date->format('Y-m-d\TH:i') !== $value) {
        return false;
    }

    return $date->format('Y-m-d H:i:s');
}

/** Cek keberadaan baris berdasarkan id. Nama tabel dibatasi whitelist. */
function idExists(mysqli $conn, string $table, int $id): bool
{
    $allowed = ['barang', 'ruangan'];
    if (!in_array($table, $allowed, true) || $id <= 0) {
        return false;
    }

    $stmt = $conn->prepare("SELECT 1 FROM {$table} WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    return (bool) $stmt->get_result()->fetch_row();
}

/**
 * Validasi + normalisasi input form laporan (tambah/edit).
 *
 * @return array{0: array<string,mixed>, 1: string[]} [data bersih, daftar error]
 */
function validateLaporanInput(mysqli $conn, array $input, ?string $existingJenisPoli = null, bool $allowNoAset = false): array
{
    $statusList    = ['Pending', 'Proses', 'Selesai'];
    $prioritasList = ['Rendah', 'Sedang', 'Tinggi'];
    $errors        = [];

    $barangId     = (int) ($input['barang_id'] ?? 0);
    $unitId       = (int) ($input['unit_id'] ?? 0);
    $jenisPoli    = trim((string) ($input['jenis_poli'] ?? ''));
    $tanggal      = trim((string) ($input['tanggal'] ?? ''));
    $waktuKejadianInput = trim((string) ($input['waktu_kejadian'] ?? ''));
    $waktuKejadian = parseLocalDateTime($waktuKejadianInput);
    $asetId       = (int) ($input['aset_id'] ?? 0);
    $noSeri       = trim((string) ($input['no_seri'] ?? ''));
    $tipe         = trim((string) ($input['tipe_barang'] ?? ''));
    $rincian      = trim((string) ($input['rincian_kerusakan'] ?? ''));
    $uraian       = trim((string) ($input['uraian_kegiatan'] ?? ''));
    $status       = (string) ($input['status'] ?? '');
    $prioritas    = (string) ($input['prioritas'] ?? '');

    if (!idExists($conn, 'barang', $barangId)) {
        $errors[] = 'Jenis barang tidak valid.';
    }
    $namaRuangan = null;
    if (!idExists($conn, 'ruangan', $unitId)) {
        $errors[] = 'Unit/ruangan tidak valid.';
    } else {
        $stmt = $conn->prepare('SELECT nama_ruangan FROM ruangan WHERE id = ?');
        $stmt->bind_param('i', $unitId);
        $stmt->execute();
        $namaRuangan = $stmt->get_result()->fetch_column();
    }

    // Bila aset dipilih dari daftar: pastikan cocok dengan unit & barang, lalu
    // isi serial & tipe dari aset (tidak percaya nilai kiriman untuk kedua itu).
    // Selain itu, unit aset WAJIB dipilih — kecuali laporan lama tanpa id_aset
    // ($allowNoAset) yang sedang diedit ulang.
    $asetTipe = null;
    if ($asetId > 0) {
        $stmt = $conn->prepare(
            'SELECT id_ruangan, id_barang, serial_number, tipe FROM aset_barang WHERE id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $asetId);
        $stmt->execute();
        $asetRow = $stmt->get_result()->fetch_assoc();

        if (!$asetRow
            || (int) $asetRow['id_ruangan'] !== $unitId
            || (int) $asetRow['id_barang'] !== $barangId) {
            $errors[] = 'Aset terpilih tidak sesuai unit/ruangan dan jenis barang.';
            $asetId = 0;
        } else {
            $noSeri   = $asetRow['serial_number'];
            $asetTipe = $asetRow['tipe'];
        }
    } elseif (!$allowNoAset) {
        $errors[] = 'Pilih unit aset yang rusak.';
    }

    if ($asetTipe !== null && $asetTipe !== '') {
        $tipe = $asetTipe;
    }

    if (mb_strtoupper(trim((string) $namaRuangan), 'UTF-8') === 'POLI') {
        $isExistingJenisPoli = $existingJenisPoli !== null
            && $jenisPoli !== ''
            && $jenisPoli === $existingJenisPoli;
        if (!in_array($jenisPoli, poliJenisOptions(), true) && !$isExistingJenisPoli) {
            $errors[] = 'Jenis poli wajib dipilih.';
        }
    } else {
        $jenisPoli = '';
    }

    if ($waktuKejadian === false) {
        $errors[] = 'Waktu kejadian/laporan diterima tidak valid.';
        $waktuKejadian = null;
    } elseif ($waktuKejadian !== null) {
        $tanggal = substr($waktuKejadian, 0, 10);
    }

    if ($tanggal === '') {
        $tanggal = date('Y-m-d');
    } elseif (!validDateYmd($tanggal)) {
        $errors[] = 'Format tanggal tidak valid.';
    }

    if ($rincian === '') {
        $errors[] = 'Rincian kerusakan wajib diisi.';
    } elseif (mb_strlen($rincian) > 300) {
        $errors[] = 'Rincian kerusakan maksimal 300 karakter.';
    }

    if (mb_strlen($uraian) > 500) {
        $errors[] = 'Uraian kegiatan maksimal 500 karakter.';
    }
    if (mb_strlen((string) $noSeri) > 50) {
        $errors[] = 'No seri maksimal 50 karakter.';
    }
    if (mb_strlen($tipe) > 80) {
        $errors[] = 'Tipe barang maksimal 80 karakter.';
    }

    // Serial kosong disimpan sebagai NULL (bukan string kosong).
    $noSeri = ($noSeri === null || trim((string) $noSeri) === '') ? null : trim((string) $noSeri);

    if (!in_array($status, $statusList, true)) {
        $errors[] = 'Status penanganan tidak valid.';
    }
    if (!in_array($prioritas, $prioritasList, true)) {
        $errors[] = 'Prioritas tidak valid.';
    }

    return [[
        'barang_id' => $barangId,
        'unit_id'   => $unitId,
        'jenis_poli' => $jenisPoli,
        'tanggal'   => $tanggal,
        'waktu_kejadian' => $waktuKejadian,
        'aset_id'   => $asetId > 0 ? $asetId : null,
        'no_seri'   => $noSeri,
        'tipe'      => $tipe,
        'rincian'   => $rincian,
        'uraian'    => $uraian,
        'status'    => $status,
        'prioritas' => $prioritas,
    ], $errors];
}
