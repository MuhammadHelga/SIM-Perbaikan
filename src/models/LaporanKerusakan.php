<?php

class LaporanKerusakan
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Bangun klausa WHERE + tipe/param untuk filter listing laporan.
     * Dipakai oleh getListing(), countFiltered(), dan statsByPeriode().
     *
     * @param array{periode?:string, status?:string, search?:string} $filters
     * @return array{0:string, 1:string, 2:array<int,mixed>} [whereSql, types, params]
     */
    private function buildFilters(array $filters): array
    {
        $where  = ' WHERE 1=1';
        $types  = '';
        $params = [];

        // Periode 'YYYY-MM' -> rentang tanggal (ramah index idx_lap_tanggal).
        $periode = (string) ($filters['periode'] ?? '');
        if ($periode !== '' && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode) === 1) {
            $start = $periode . '-01';
            $end   = date('Y-m-d', strtotime($start . ' +1 month'));

            $where   .= ' AND lk.tanggal >= ? AND lk.tanggal < ?';
            $types   .= 'ss';
            $params[] = $start;
            $params[] = $end;
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status !== '') {
            $where   .= ' AND lk.status_penanganan = ?';
            $types   .= 's';
            $params[] = $status;
        }

        $search = (string) ($filters['search'] ?? '');
        if ($search !== '') {
            // Netralkan wildcard LIKE agar dicari sebagai teks biasa.
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';

            $where .= ' AND (r.nama_ruangan LIKE ? OR lk.jenis_poli LIKE ? OR b.nama_barang LIKE ?'
                    . ' OR lk.rincian_kerusakan LIKE ? OR lk.serial_number LIKE ?)';
            $types .= 'sssss';
            for ($i = 0; $i < 5; $i++) {
                $params[] = $like;
            }
        }

        // Filter status ACC surat (untuk panel kabag).
        $acc = (string) ($filters['acc'] ?? '');
        if ($acc === 'menunggu' || $acc === 'diacc') {
            $where   .= ' AND s.acc_status = ?';
            $types   .= 's';
            $params[] = $acc;
        }

        return [$where, $types, $params];
    }

    /**
     * SELECT dasar untuk listing laporan (tanpa WHERE/ORDER).
     */
    private function listingSelect(): string
    {
        return "SELECT
                    lk.id,
                    lk.tanggal,
                    lk.waktu_kejadian,
                    lk.created_at,
                    r.nama_ruangan       AS urusan,
                    lk.jenis_poli,
                    b.nama_barang        AS barang,
                    b.id                 AS barang_id,
                    lk.id_aset,
                    lk.tipe_barang,
                    lk.serial_number,
                    lk.rincian_kerusakan AS kerusakan,
                    lk.uraian_kegiatan   AS uraian,
                    lk.status_penanganan,
                    lk.prioritas,
                    lk.kirim_status,
                    lk.tgl_kirim,
                    lk.tgl_terima,
                    s.verify_uid,
                    s.nama_pelapor,
                    s.jabatan_pelapor,
                    s.nomor_surat,
                    s.tgl_surat,
                    s.acc_status,
                    s.acc_oleh,
                    s.tgl_acc,
                    u.nama             AS nama_kabag,
                    u.jabatan          AS jabatan_kabag,
                    u.username         AS username_kabag,
                    CASE WHEN lk.status_penanganan = 'Selesai' THEN 'selesai' ELSE 'pending' END AS hasil
                FROM laporan_kerusakan lk
                INNER JOIN barang  b ON lk.id_barang  = b.id
                INNER JOIN ruangan r ON lk.id_ruangan = r.id
                LEFT JOIN surat_kerusakan s ON s.id_laporan = lk.id
                LEFT JOIN users u ON u.id = s.acc_oleh";
    }

    /**
     * Ambil satu halaman laporan sesuai filter (LIMIT/OFFSET).
     *
     * @param array{periode?:string, status?:string, search?:string} $filters
     * @return array<int, array<string,mixed>>
     */
    public function getListing(array $filters, int $limit, int $offset): array
    {
        [$where, $types, $params] = $this->buildFilters($filters);

        $sql = $this->listingSelect()
             . $where
             . " ORDER BY lk.tanggal DESC, lk.id DESC LIMIT ? OFFSET ?";

        $types   .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        $rows = array_map([$this, 'attachVerifyToken'], $stmt->get_result()->fetch_all(MYSQLI_ASSOC));

        return $this->attachSeringRusak($rows);
    }

    /**
     * Semua baris sesuai filter, tanpa LIMIT — dipakai untuk cetak rekap/export.
     *
     * @param array{periode?:string, status?:string, search?:string} $filters
     * @return array<int, array<string,mixed>>
     */
    public function getAllFiltered(array $filters): array
    {
        [$where, $types, $params] = $this->buildFilters($filters);

        $sql = $this->listingSelect()
             . $where
             . " ORDER BY lk.tanggal DESC, lk.id DESC";

        $stmt = $this->conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        $rows = array_map([$this, 'attachVerifyToken'], $stmt->get_result()->fetch_all(MYSQLI_ASSOC));

        return $this->attachSeringRusak($rows);
    }

    /**
     * Peta id_aset => jumlah laporan dalam N hari terakhir untuk aset yang
     * jumlahnya mencapai ambang "sering rusak".
     *
     * @return array<int,int>
     */
    private function seringRusakMap(): array
    {
        $hari = defined('SERING_RUSAK_HARI') ? (int) SERING_RUSAK_HARI : 90;
        $min  = defined('SERING_RUSAK_MIN') ? (int) SERING_RUSAK_MIN : 3;
        $hari = max(1, $hari);
        $min  = max(1, $min);

        $sql = "SELECT id_aset, COUNT(*) AS jumlah
                FROM laporan_kerusakan
                WHERE id_aset IS NOT NULL
                  AND tanggal >= DATE_SUB(CURDATE(), INTERVAL {$hari} DAY)
                GROUP BY id_aset
                HAVING COUNT(*) >= {$min}";

        $res = $this->conn->query($sql);
        $map = [];
        while ($row = $res->fetch_assoc()) {
            $map[(int) $row['id_aset']] = (int) $row['jumlah'];
        }

        return $map;
    }

    /**
     * Lengkapi baris listing dengan indikator "sering rusak" (per aset fisik).
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function attachSeringRusak(array $rows): array
    {
        if (!$rows) {
            return $rows;
        }

        $map = $this->seringRusakMap();

        foreach ($rows as &$row) {
            $idAset = (int) ($row['id_aset'] ?? 0);
            $row['sering_rusak'] = $idAset > 0 && isset($map[$idAset]);
            $row['kerusakan_90h'] = $idAset > 0 ? ($map[$idAset] ?? 0) : 0;
        }
        unset($row);

        return $rows;
    }
    /**
     * Hitung jumlah baris sesuai filter (untuk paginasi).
     *
     * @param array{periode?:string, status?:string, search?:string} $filters
     */
    public function countFiltered(array $filters): int
    {
        [$where, $types, $params] = $this->buildFilters($filters);

        $sql = "SELECT COUNT(*) AS j
                FROM laporan_kerusakan lk
                INNER JOIN barang  b ON lk.id_barang  = b.id
                INNER JOIN ruangan r ON lk.id_ruangan = r.id
                LEFT JOIN surat_kerusakan s ON s.id_laporan = lk.id"
                . $where;

        $stmt = $this->conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        return (int) $stmt->get_result()->fetch_assoc()['j'];
    }

    /**
     * Statistik kartu, hanya dibatasi periode (bukan status/cari).
     *
     * @return array{total:int, pending:int, selesai:int}
     */
    public function statsByPeriode(string $periode): array
    {
        [$where, $types, $params] = $this->buildFilters(['periode' => $periode]);

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status_penanganan = 'Pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status_penanganan = 'Selesai' THEN 1 ELSE 0 END) AS selesai
                FROM laporan_kerusakan lk"
                . $where;

        $stmt = $this->conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        $row     = $stmt->get_result()->fetch_assoc();
        $total   = (int) $row['total'];
        $pending = (int) $row['pending'];
        $selesai = (int) $row['selesai'];

        return [
            'total'   => $total,
            'pending' => $pending,
            'selesai' => $selesai,
        ];
    }

    // READ BY ID
    public function getById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT
                lk.*,
                b.nama_barang        AS barang,
                r.nama_ruangan       AS urusan,
                s.verify_uid,
                s.nama_pelapor,
                s.jabatan_pelapor,
                s.nomor_surat,
                s.tgl_surat,
                s.acc_status,
                s.acc_oleh,
                s.tgl_acc,
                u.nama             AS nama_kabag,
                u.jabatan          AS jabatan_kabag,
                u.username         AS username_kabag,
                CASE WHEN lk.status_penanganan = 'Selesai' THEN 'selesai' ELSE 'pending' END AS hasil
             FROM laporan_kerusakan lk
             INNER JOIN barang  b ON lk.id_barang  = b.id
             INNER JOIN ruangan r ON lk.id_ruangan = r.id
             LEFT JOIN surat_kerusakan s ON s.id_laporan = lk.id
             LEFT JOIN users u ON u.id = s.acc_oleh
             WHERE lk.id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->attachVerifyToken($row) : null;
    }

    public function recordHistory(
        int $reportId,
        ?int $userId,
        string $actorName,
        string $action,
        string $detail,
        ?string $statusLama = null,
        ?string $statusBaru = null
    ): bool {
        $stmt = $this->conn->prepare(
            "INSERT INTO laporan_riwayat
                (laporan_id, user_id, actor_name, aksi, detail, status_lama, status_baru)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "iisssss",
            $reportId,
            $userId,
            $actorName,
            $action,
            $detail,
            $statusLama,
            $statusBaru
        );

        return $stmt->execute();
    }

    /** @return array<int, array{aksi:string, detail:string, actor_name:string, created_at:string}> */
    public function getHistory(int $reportId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT aksi, detail, actor_name, created_at
             FROM laporan_riwayat
             WHERE laporan_id = ?
             ORDER BY created_at DESC, id DESC"
        );
        $stmt->bind_param("i", $reportId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Ambil laporan berdasarkan token verifikasi (untuk halaman verifikasi QR).
     * Token diverifikasi tanda tangan HMAC-nya, lalu dicari lewat verify_uid.
     * Mengembalikan null bila token kosong, tanda tangan salah, atau tidak ada.
     */
    public function getByVerifyToken(string $token)
    {
        $uid = self::verifyUidFromToken($token);
        if ($uid === null) {
            return null;
        }

        $stmt = $this->conn->prepare(
            "SELECT
                lk.*,
                b.nama_barang        AS barang,
                r.nama_ruangan       AS urusan,
                s.verify_uid,
                s.nama_pelapor,
                s.jabatan_pelapor,
                s.nomor_surat,
                s.tgl_surat,
                s.acc_status,
                s.acc_oleh,
                s.tgl_acc,
                u.nama             AS nama_kabag,
                u.jabatan          AS jabatan_kabag,
                u.username         AS username_kabag,
                CASE WHEN lk.status_penanganan = 'Selesai' THEN 'selesai' ELSE 'pending' END AS hasil
             FROM surat_kerusakan s
             INNER JOIN laporan_kerusakan lk ON lk.id = s.id_laporan
             INNER JOIN barang  b ON lk.id_barang  = b.id
             INNER JOIN ruangan r ON lk.id_ruangan = r.id
             LEFT JOIN users u ON u.id = s.acc_oleh
             WHERE s.verify_uid = ?"
        );

        $stmt->bind_param("s", $uid);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $this->attachVerifyToken($row) : null;
    }

    /**
     * Token verifikasi yang dipakai di QR: "<uid>.<hmac>". Tidak disimpan di DB,
     * selalu dihitung dari verify_uid + APP_SECRET.
     */
    public static function verifyTokenFor(string $uid): string
    {
        $mac = hash_hmac('sha256', $uid, (string) APP_SECRET);
        return $uid . '.' . substr($mac, 0, 32);
    }

    /**
     * Validasi tanda tangan token; kembalikan uid bila sah, atau null.
     */
    public static function verifyUidFromToken(string $token): ?string
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        $uid      = $parts[0];
        $expected = substr(hash_hmac('sha256', $uid, (string) APP_SECRET), 0, 32);

        return hash_equals($expected, $parts[1]) ? $uid : null;
    }

    /**
     * Lengkapi satu baris dengan token tampilan (untuk QR) dari verify_uid.
     */
    private function attachVerifyToken(array $row): array
    {
        $row['verify_token'] = !empty($row['verify_uid'])
            ? self::verifyTokenFor((string) $row['verify_uid'])
            : null;

        return $row;
    }

    /**
     * Pastikan laporan punya baris surat (beserta uid). INSERT IGNORE membuat
     * pemanggilan berulang aman (UNIQUE id_laporan).
     */
    private function ensureSurat($idLaporan): void
    {
        $uid = bin2hex(random_bytes(16));
        $stmt = $this->conn->prepare(
            "INSERT IGNORE INTO surat_kerusakan (id_laporan, verify_uid) VALUES (?, ?)"
        );
        $stmt->bind_param("is", $idLaporan, $uid);
        $stmt->execute();
    }

    // CREATE
    public function create(
        $id_barang,
        $id_ruangan,
        $jenis_poli,
        $tanggal,
        $waktu_kejadian,
        $serial_number,
        $rincian_kerusakan,
        $uraian_kegiatan,
        $status_penanganan,
        $prioritas = 'Sedang',
        $id_user = null,
        $tipe_barang = null,
        $id_aset = null
    ) {
        $id_aset = ($id_aset !== null && (int) $id_aset > 0) ? (int) $id_aset : null;
        $tipe_barang = $tipe_barang !== null && $tipe_barang !== '' ? $tipe_barang : null;

        $stmt = $this->conn->prepare(
            "INSERT INTO laporan_kerusakan
            (
                id_barang,
                id_ruangan,
                id_aset,
                jenis_poli,
                tanggal,
                waktu_kejadian,
                serial_number,
                tipe_barang,
                rincian_kerusakan,
                uraian_kegiatan,
                status_penanganan,
                prioritas,
                id_user
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iiisssssssssi",
            $id_barang,
            $id_ruangan,
            $id_aset,
            $jenis_poli,
            $tanggal,
            $waktu_kejadian,
            $serial_number,
            $tipe_barang,
            $rincian_kerusakan,
            $uraian_kegiatan,
            $status_penanganan,
            $prioritas,
            $id_user
        );

        $ok = $stmt->execute();
        if ($ok) {
            $id = (int) $this->conn->insert_id;
            $this->ensureSurat($id);
            return $id;
        }

        return false;
    }

    // UPDATE
    public function update(
        $id,
        $id_barang,
        $id_ruangan,
        $jenis_poli,
        $tanggal,
        $waktu_kejadian,
        $serial_number,
        $rincian_kerusakan,
        $uraian_kegiatan,
        $status_penanganan,
        $prioritas = 'Sedang',
        $tipe_barang = null,
        $id_aset = null
    ) {
        $id_aset = ($id_aset !== null && (int) $id_aset > 0) ? (int) $id_aset : null;
        $tipe_barang = $tipe_barang !== null && $tipe_barang !== '' ? $tipe_barang : null;

        $stmt = $this->conn->prepare(
            "UPDATE laporan_kerusakan
             SET
                id_barang = ?,
                id_ruangan = ?,
                id_aset = ?,
                jenis_poli = ?,
                tanggal = ?,
                waktu_kejadian = ?,
                serial_number = ?,
                tipe_barang = ?,
                rincian_kerusakan = ?,
                uraian_kegiatan = ?,
                status_penanganan = ?,
                prioritas = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "iiisssssssssi",
            $id_barang,
            $id_ruangan,
            $id_aset,
            $jenis_poli,
            $tanggal,
            $waktu_kejadian,
            $serial_number,
            $tipe_barang,
            $rincian_kerusakan,
            $uraian_kegiatan,
            $status_penanganan,
            $prioritas,
            $id
        );

        return $stmt->execute();
    }

    // DELETE
    public function delete($id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM laporan_kerusakan
             WHERE id = ?"
        );

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    // ===== ALUR KIRIM / TERIMA (lihat plan §1.1 — Opsi 2) =====

    /**
     * Tandai barang sudah dikirim.
     * Set kirim_status='dikirim' + tgl_kirim, dan naikkan status Pending -> Proses.
     */
    public function kirim($id, $tgl)
    {
        $stmt = $this->conn->prepare(
            "UPDATE laporan_kerusakan
             SET
                kirim_status = 'dikirim',
                tgl_kirim = ?,
                status_penanganan = IF(status_penanganan = 'Pending', 'Proses', status_penanganan)
             WHERE id = ?
               AND kirim_status = 'belum'
               AND status_penanganan IN ('Pending', 'Proses')"
        );

        $stmt->bind_param("si", $tgl, $id);

        return $stmt->execute() && $stmt->affected_rows === 1;
    }

    /**
     * Tandai barang sudah diterima kembali.
     * Hanya mengisi tgl_terima + kirim_status; tgl_kirim dibiarkan utuh.
     */
    public function terima($id, $tgl)
    {
        $stmt = $this->conn->prepare(
            "UPDATE laporan_kerusakan
             SET
                kirim_status = 'diterima',
                tgl_terima = ?
             WHERE id = ? AND kirim_status = 'dikirim'"
        );

        $stmt->bind_param("si", $tgl, $id);

        return $stmt->execute() && $stmt->affected_rows === 1;
    }

    /**
     * Simpan data penandatangan surat agar halaman verifikasi & cetak ulang
     * menampilkan nama/nomor yang sama dengan surat tercetak.
     * $tglSurat boleh null.
     */
    public function simpanSurat($id, string $nama, string $jabatan, string $nomor, ?string $tglSurat)
    {
        // Jaga-jaga untuk laporan lama yang belum punya baris surat.
        $this->ensureSurat((int) $id);

        $stmt = $this->conn->prepare(
            "UPDATE surat_kerusakan
             SET
                nama_pelapor = ?,
                jabatan_pelapor = ?,
                nomor_surat = ?,
                tgl_surat = ?
             WHERE id_laporan = ?"
        );

        $stmt->bind_param("ssssi", $nama, $jabatan, $nomor, $tglSurat, $id);

        return $stmt->execute();
    }

    /**
     * ACC surat oleh kabag: tandai acc_status='diacc' + pencatat siapa & kapan.
     * Pastikan laporan sudah punya baris surat dan belum pernah di-ACC.
     *
     * @return array{ok:bool, reason?:string}
     */
    public function acc(int $idLaporan, int $idUser): array
    {
        $this->ensureSurat($idLaporan);

        $stmt = $this->conn->prepare(
            "SELECT acc_status FROM surat_kerusakan WHERE id_laporan = ? LIMIT 1"
        );
        $stmt->bind_param("i", $idLaporan);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            return ['ok' => false, 'reason' => 'Surat tidak ditemukan.'];
        }
        if (($row['acc_status'] ?? '') === 'diacc') {
            return ['ok' => false, 'reason' => 'Surat sudah pernah di-ACC.'];
        }

        $stmt = $this->conn->prepare(
            "UPDATE surat_kerusakan
             SET acc_status = 'diacc',
                 acc_oleh = ?,
                 tgl_acc = NOW()
             WHERE id_laporan = ? AND acc_status = 'menunggu'"
        );
        $stmt->bind_param("ii", $idUser, $idLaporan);
        $stmt->execute();

        return $stmt->affected_rows > 0
            ? ['ok' => true]
            : ['ok' => false, 'reason' => 'Surat sudah pernah di-ACC.'];
    }

    // DATA BARANG UNTUK DROPDOWN
    public function getBarang()
    {
        $stmt = $this->conn->prepare(
            "SELECT id, nama_barang
             FROM barang
             WHERE aktif = 1
             ORDER BY nama_barang ASC"
        );

        $stmt->execute();

        return $stmt->get_result();
    }

    // DATA RUANGAN UNTUK DROPDOWN
    public function getRuangan()
    {
        $stmt = $this->conn->prepare(
            "SELECT id, nama_ruangan
             FROM ruangan
             WHERE aktif = 1
             ORDER BY nama_ruangan ASC"
        );

        $stmt->execute();

        return $stmt->get_result();
    }

    // ===== AGREGAT UNTUK DASHBOARD =====

    /**
     * Bangun klausa periode berbasis rentang tanggal (ramah index idx_lap_tanggal).
     * Contoh: tahun=2026 -> " AND tanggal >= ? AND tanggal < ?" (2026-01-01 .. 2027-01-01).
     * Mengisi $types & $params untuk bind_param.
     */
    private function periodeClause(?int $tahun, ?int $bulan, array &$types, array &$params): string
    {
        if ($tahun === null) {
            if ($bulan === null) {
                return '';
            }

            // Bulan tanpa tahun tidak bisa jadi rentang tanggal.
            $types[]  = 'i';
            $params[] = $bulan;
            return ' AND MONTH(tanggal) = ?';
        }

        $start = sprintf('%04d-%02d-01', $tahun, $bulan ?? 1);
        $end   = $bulan === null
            ? sprintf('%04d-01-01', $tahun + 1)
            : date('Y-m-d', strtotime($start . ' +1 month'));

        $types[]  = 's';
        $params[] = $start;
        $types[]  = 's';
        $params[] = $end;

        return ' AND tanggal >= ? AND tanggal < ?';
    }

    private function countWhere(string $where, ?int $tahun, ?int $bulan): int
    {
        $types  = [];
        $params = [];
        $sql = "SELECT COUNT(*) AS j FROM laporan_kerusakan WHERE 1=1 " . $where
             . $this->periodeClause($tahun, $bulan, $types, $params);

        $stmt = $this->conn->prepare($sql);
        if ($types) {
            $stmt->bind_param(implode('', $types), ...$params);
        }
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['j'];
    }

    public function countAll(?int $tahun = null, ?int $bulan = null): int
    {
        return $this->countWhere('', $tahun, $bulan);
    }

    public function countDalamPenanganan(?int $tahun = null, ?int $bulan = null): int
    {
        return $this->countWhere("AND status_penanganan IN ('Pending','Proses')", $tahun, $bulan);
    }

    public function countSelesai(?int $tahun = null, ?int $bulan = null): int
    {
        return $this->countWhere("AND status_penanganan = 'Selesai'", $tahun, $bulan);
    }

    public function countKritis(?int $tahun = null, ?int $bulan = null): int
    {
        return $this->countWhere("AND prioritas = 'Tinggi' AND status_penanganan <> 'Selesai'", $tahun, $bulan);
    }

    public function countPeriode(int $tahun, ?int $bulan = null): int
    {
        $types  = [];
        $params = [];
        $sql = "SELECT COUNT(*) AS j FROM laporan_kerusakan WHERE 1=1"
             . $this->periodeClause($tahun, $bulan, $types, $params);

        $stmt = $this->conn->prepare($sql);
        if ($types) {
            $stmt->bind_param(implode('', $types), ...$params);
        }
        $stmt->execute();

        return (int) $stmt->get_result()->fetch_assoc()['j'];
    }

    /**
     * Rekap jumlah laporan per bulan dalam satu tahun, dipisah per status.
     * @return array{masuk:int[], selesai:int[], pending:int[], proses:int[]} indeks 1..12
     */
    public function monthlyRecap(int $tahun): array
    {
        $masuk   = array_fill(1, 12, 0);
        $selesai = array_fill(1, 12, 0);
        $pending = array_fill(1, 12, 0);
        $proses  = array_fill(1, 12, 0);

        $types  = [];
        $params = [];
        $where  = $this->periodeClause($tahun, null, $types, $params);

        $stmt = $this->conn->prepare(
            "SELECT MONTH(tanggal) AS bulan, status_penanganan AS status, COUNT(*) AS j
             FROM laporan_kerusakan
             WHERE 1=1" . $where . "
             GROUP BY MONTH(tanggal), status_penanganan"
        );
        if ($types) {
            $stmt->bind_param(implode('', $types), ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $bulan  = (int) $row['bulan'];
            $jumlah = (int) $row['j'];

            $masuk[$bulan] += $jumlah;
            if ($row['status'] === 'Selesai') {
                $selesai[$bulan] += $jumlah;
            } elseif ($row['status'] === 'Proses') {
                $proses[$bulan] += $jumlah;
            } else {
                $pending[$bulan] += $jumlah;
            }
        }

        return ['masuk' => $masuk, 'selesai' => $selesai, 'pending' => $pending, 'proses' => $proses];
    }

    /**
     * Jumlah laporan per barang, urut terbanyak.
     * Dapat dibatasi periode (tahun/bulan) agar cocok dengan label grafik.
     * @return array<int, array{nama:string, jumlah:int}>
     */
    public function countByBarang(?int $tahun = null, ?int $bulan = null): array
    {
        $types  = [];
        $params = [];
        $where  = $this->periodeClause($tahun, $bulan, $types, $params);

        $stmt = $this->conn->prepare(
            "SELECT b.nama_barang AS nama, COUNT(*) AS jumlah
             FROM laporan_kerusakan lk
             INNER JOIN barang b ON lk.id_barang = b.id
             WHERE 1=1" . $where . "
             GROUP BY lk.id_barang, b.nama_barang
             ORDER BY jumlah DESC"
        );

        if ($types) {
            $stmt->bind_param(implode('', $types), ...$params);
        }
        $stmt->execute();

        $out = [];
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $out[] = ['nama' => $row['nama'], 'jumlah' => (int) $row['jumlah']];
        }
        return $out;
    }

    /**
     * Rekap jumlah laporan per barang untuk setiap bulan dalam satu tahun.
     * @return array<int, array{nama:string, bulanan:int[]}>
     */
    public function monthlyByBarang(int $tahun): array
    {
        $barang = [];
        $types  = [];
        $params = [];
        $where  = $this->periodeClause($tahun, null, $types, $params);

        $stmt = $this->conn->prepare(
            "SELECT b.id, b.nama_barang AS nama, MONTH(lk.tanggal) AS bulan, COUNT(*) AS jumlah
             FROM laporan_kerusakan lk
             INNER JOIN barang b ON lk.id_barang = b.id
             WHERE 1=1" . $where . "
             GROUP BY b.id, b.nama_barang, MONTH(lk.tanggal)"
        );
        if ($types) {
            $stmt->bind_param(implode('', $types), ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $id = (int) $row['id'];
            if (!isset($barang[$id])) {
                $barang[$id] = [
                    'nama' => $row['nama'],
                    'bulanan' => array_fill(1, 12, 0),
                    'total' => 0,
                ];
            }

            $jumlah = (int) $row['jumlah'];
            $barang[$id]['bulanan'][(int) $row['bulan']] = $jumlah;
            $barang[$id]['total'] += $jumlah;
        }

        usort($barang, static function (array $a, array $b): int {
            return $b['total'] <=> $a['total'];
        });

        return array_map(static function (array $item): array {
            return ['nama' => $item['nama'], 'bulanan' => $item['bulanan']];
        }, $barang);
    }

    /**
     * Jumlah laporan per jenis barang untuk setiap tahun (lintas tahun).
     *
     * Deret diselaraskan dengan daftar $tahun yang diberikan supaya semua garis
     * punya panjang sama dan titik-titiknya sejajar di sumbu X. Barang yang tidak
     * punya data di suatu tahun diisi 0. Barang diurutkan dari total terbanyak.
     *
     * @param int[] $tahun daftar tahun yang ditampilkan, mis. [2024, 2025, 2026]
     * @return array{tahun:int[], barang:array<int,array{nama:string, tahunan:int[]}>, total:int[]}
     */
    public function countByBarangPerTahun(array $tahun): array
    {
        $tahun = array_values(array_unique(array_map('intval', $tahun)));
        sort($tahun);

        if (!$tahun) {
            return ['tahun' => [], 'barang' => [], 'total' => []];
        }

        $start = sprintf('%04d-01-01', $tahun[0]);
        $end   = sprintf('%04d-01-01', $tahun[count($tahun) - 1] + 1);

        $stmt = $this->conn->prepare(
            "SELECT YEAR(lk.tanggal) AS tahun, b.id, b.nama_barang AS nama, COUNT(*) AS jumlah
             FROM laporan_kerusakan lk
             INNER JOIN barang b ON lk.id_barang = b.id
             WHERE lk.tanggal >= ? AND lk.tanggal < ?
             GROUP BY YEAR(lk.tanggal), b.id, b.nama_barang"
        );
        $stmt->bind_param("ss", $start, $end);
        $stmt->execute();
        $res = $stmt->get_result();

        $posisi = array_flip($tahun); // tahun => indeks kolom
        $jumlah = count($tahun);
        $barang = []; // id => ['nama' => string, 'tahunan' => int[], 'total' => int]

        while ($row = $res->fetch_assoc()) {
            $th = (int) $row['tahun'];
            if (!isset($posisi[$th])) {
                continue;
            }

            $id = (int) $row['id'];
            if (!isset($barang[$id])) {
                $barang[$id] = [
                    'nama'    => $row['nama'],
                    'tahunan' => array_fill(0, $jumlah, 0),
                    'total'   => 0,
                ];
            }

            $j = (int) $row['jumlah'];
            $barang[$id]['tahunan'][$posisi[$th]] += $j;
            $barang[$id]['total'] += $j;
        }

        // Terbanyak lebih dulu agar warna/urutan garis konsisten.
        uasort($barang, static function (array $a, array $b): int {
            return $b['total'] <=> $a['total'];
        });

        $total = array_fill(0, $jumlah, 0);
        $out   = [];
        foreach ($barang as $item) {
            $out[] = ['nama' => $item['nama'], 'tahunan' => $item['tahunan']];
            foreach ($item['tahunan'] as $i => $j) {
                $total[$i] += $j;
            }
        }

        return ['tahun' => $tahun, 'barang' => $out, 'total' => $total];
    }

    /**
     * Daftar tahun yang punya data laporan (+ tahun berjalan), urut menurun.
     * @return int[]
     */
    public function tahunTersedia(): array
    {
        $years = [];
        $res = $this->conn->query(
            "SELECT DISTINCT YEAR(tanggal) AS th FROM laporan_kerusakan ORDER BY th DESC"
        );
        while ($row = $res->fetch_assoc()) {
            $years[] = (int) $row['th'];
        }

        $now = (int) date('Y');
        if (!in_array($now, $years, true)) {
            $years[] = $now;
        }

        $years = array_values(array_unique($years));
        rsort($years);

        return $years;
    }

    /**
     * Rekap laporan status 'Selesai' per ruangan per bulan (satu tahun).
     * Semua ruangan tetap muncul walau tidak ada data (diisi 0).
     *
     * @return array<int, array{id:int, nama:string, bulan:int[], total:int}>
     */
    public function recapSelesaiByRuangan(int $tahun): array
    {
        $start = sprintf('%04d-01-01', $tahun);
        $end   = sprintf('%04d-01-01', $tahun + 1);

        $stmt = $this->conn->prepare(
            "SELECT r.id, r.nama_ruangan,
                    MONTH(lk.tanggal) AS bulan,
                    COUNT(lk.id) AS j
             FROM ruangan r
             LEFT JOIN laporan_kerusakan lk
                    ON lk.id_ruangan = r.id
                   AND lk.status_penanganan = 'Selesai'
                   AND lk.tanggal >= ? AND lk.tanggal < ?
             GROUP BY r.id, r.nama_ruangan, MONTH(lk.tanggal)
             ORDER BY r.nama_ruangan ASC"
        );
        $stmt->bind_param('ss', $start, $end);
        $stmt->execute();
        $res = $stmt->get_result();

        $index = [];
        while ($row = $res->fetch_assoc()) {
            $id = (int) $row['id'];

            if (!isset($index[$id])) {
                $index[$id] = [
                    'id'    => $id,
                    'nama'  => $row['nama_ruangan'],
                    'bulan' => array_fill(1, 12, 0),
                    'total' => 0,
                ];
            }

            if ($row['bulan'] !== null) {
                $j = (int) $row['j'];
                $index[$id]['bulan'][(int) $row['bulan']] = $j;
                $index[$id]['total'] += $j;
            }
        }

        return array_values($index);
    }

    /**
     * Rata-rata durasi penyelesaian (hari) laporan yang ditangani internal.
     *
     * Cakupan: status 'Selesai' dan tidak dikirim ke vendor (tgl_kirim IS NULL).
     * Titik mulai  = waktu_kejadian, fallback created_at.
     * Waktu selesai = riwayat dengan status_baru = 'Selesai' (termasuk laporan
     *                 yang lahir berstatus 'Selesai'). Laporan tanpa baris
     *                 riwayat yang sesuai dilewati (mode ketat).
     *
     * @return array{days: float|null, count: int}
     */
    public function avgServiceDurationDays(?int $tahun = null): array
    {
        $types  = [];
        $params = [];
        $where  = $this->periodeClause($tahun, null, $types, $params);

        $stmt = $this->conn->prepare(
            "SELECT AVG(GREATEST(0, TIMESTAMPDIFF(HOUR,
                        COALESCE(lk.waktu_kejadian, lk.created_at), h.finish_at)) / 24) AS avg_days,
                    COUNT(*) AS n
             FROM laporan_kerusakan lk
             INNER JOIN (
                 SELECT laporan_id, MIN(created_at) AS finish_at
                 FROM laporan_riwayat
                 WHERE status_baru = 'Selesai'
                 GROUP BY laporan_id
             ) h ON h.laporan_id = lk.id
             WHERE lk.status_penanganan = 'Selesai'
               AND lk.tgl_kirim IS NULL
               AND h.finish_at IS NOT NULL"
            . $where
        );

        if ($types !== '') {
            $stmt->bind_param(implode('', $types), ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        $avg = $row['avg_days'] ?? null;

        return [
            'days'  => $avg === null ? null : (float) $avg,
            'count' => (int) ($row['n'] ?? 0),
        ];
    }
}
