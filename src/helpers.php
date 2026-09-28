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
function validateLaporanInput(mysqli $conn, array $input): array
{
    $statusList    = ['Pending', 'Proses', 'Selesai'];
    $prioritasList = ['Rendah', 'Sedang', 'Tinggi'];
    $errors        = [];

    $barangId     = (int) ($input['barang_id'] ?? 0);
    $unitId       = (int) ($input['unit_id'] ?? 0);
    $tanggal      = trim((string) ($input['tanggal'] ?? ''));
    $noSeri       = trim((string) ($input['no_seri'] ?? ''));
    $rincian      = trim((string) ($input['rincian_kerusakan'] ?? ''));
    $uraian       = trim((string) ($input['uraian_kegiatan'] ?? ''));
    $status       = (string) ($input['status'] ?? '');
    $prioritas    = (string) ($input['prioritas'] ?? '');

    if (!idExists($conn, 'barang', $barangId)) {
        $errors[] = 'Jenis barang tidak valid.';
    }
    if (!idExists($conn, 'ruangan', $unitId)) {
        $errors[] = 'Unit/ruangan tidak valid.';
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
    if (mb_strlen($noSeri) > 50) {
        $errors[] = 'No seri maksimal 50 karakter.';
    }

    if (!in_array($status, $statusList, true)) {
        $errors[] = 'Status penanganan tidak valid.';
    }
    if (!in_array($prioritas, $prioritasList, true)) {
        $errors[] = 'Prioritas tidak valid.';
    }

    return [[
        'barang_id' => $barangId,
        'unit_id'   => $unitId,
        'tanggal'   => $tanggal,
        'no_seri'   => $noSeri,
        'rincian'   => $rincian,
        'uraian'    => $uraian,
        'status'    => $status,
        'prioritas' => $prioritas,
    ], $errors];
}
