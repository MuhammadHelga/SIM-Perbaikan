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

            $where .= ' AND (r.nama_ruangan LIKE ? OR b.nama_barang LIKE ?'
                    . ' OR lk.rincian_kerusakan LIKE ? OR lk.serial_number LIKE ?)';
            $types .= 'ssss';
            for ($i = 0; $i < 4; $i++) {
                $params[] = $like;
            }
        }

        return [$where, $types, $params];
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

        $sql = "SELECT
                    lk.id,
                    lk.tanggal,
                    r.nama_ruangan       AS urusan,
                    b.nama_barang        AS barang,
                    lk.serial_number,
                    lk.rincian_kerusakan AS kerusakan,
                    lk.uraian_kegiatan   AS uraian,
                    lk.status_penanganan,
                    lk.prioritas,
                    lk.kirim_status,
                    lk.tgl_kirim,
                    lk.tgl_terima,
                    CASE WHEN lk.status_penanganan = 'Selesai' THEN 'selesai' ELSE 'pending' END AS hasil
                FROM laporan_kerusakan lk
                INNER JOIN barang  b ON lk.id_barang  = b.id
                INNER JOIN ruangan r ON lk.id_ruangan = r.id"
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

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
                INNER JOIN ruangan r ON lk.id_ruangan = r.id"
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
        $selesai = (int) $row['selesai'];

        return [
            'total'   => $total,
            'pending' => $total - $selesai,
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
                CASE WHEN lk.status_penanganan = 'Selesai' THEN 'selesai' ELSE 'pending' END AS hasil
             FROM laporan_kerusakan lk
             INNER JOIN barang  b ON lk.id_barang  = b.id
             INNER JOIN ruangan r ON lk.id_ruangan = r.id
             WHERE lk.id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // CREATE
    public function create(
        $id_barang,
        $id_ruangan,
        $tanggal,
        $serial_number,
        $rincian_kerusakan,
        $uraian_kegiatan,
        $status_penanganan,
        $prioritas = 'Sedang',
        $id_user = null
    ) {
        $stmt = $this->conn->prepare(
            "INSERT INTO laporan_kerusakan
            (
                id_barang,
                id_ruangan,
                tanggal,
                serial_number,
                rincian_kerusakan,
                uraian_kegiatan,
                status_penanganan,
                prioritas,
                id_user
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iissssssi",
            $id_barang,
            $id_ruangan,
            $tanggal,
            $serial_number,
            $rincian_kerusakan,
            $uraian_kegiatan,
            $status_penanganan,
            $prioritas,
            $id_user
        );

        return $stmt->execute();
    }

    // UPDATE
    public function update(
        $id,
        $id_barang,
        $id_ruangan,
        $tanggal,
        $serial_number,
        $rincian_kerusakan,
        $uraian_kegiatan,
        $status_penanganan,
        $prioritas = 'Sedang'
    ) {
        $stmt = $this->conn->prepare(
            "UPDATE laporan_kerusakan
             SET
                id_barang = ?,
                id_ruangan = ?,
                tanggal = ?,
                serial_number = ?,
                rincian_kerusakan = ?,
                uraian_kegiatan = ?,
                status_penanganan = ?,
                prioritas = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "iissssssi",
            $id_barang,
            $id_ruangan,
            $tanggal,
            $serial_number,
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
             WHERE id = ?"
        );

        $stmt->bind_param("si", $tgl, $id);

        return $stmt->execute();
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
             WHERE id = ?"
        );

        $stmt->bind_param("si", $tgl, $id);

        return $stmt->execute();
    }

    // DATA BARANG UNTUK DROPDOWN
    public function getBarang()
    {
        $stmt = $this->conn->prepare(
            "SELECT id, nama_barang
             FROM barang
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
     * Rekap jumlah laporan per bulan dalam satu tahun.
     * @return array{masuk:int[], selesai:int[]} indeks 1..12
     */
    public function monthlyRecap(int $tahun): array
    {
        $masuk   = array_fill(1, 12, 0);
        $selesai = array_fill(1, 12, 0);

        $types  = [];
        $params = [];
        $where  = $this->periodeClause($tahun, null, $types, $params);

        $stmt = $this->conn->prepare(
            "SELECT MONTH(tanggal) AS bulan, COUNT(*) AS j
             FROM laporan_kerusakan
             WHERE 1=1" . $where . "
             GROUP BY MONTH(tanggal)"
        );
        if ($types) {
            $stmt->bind_param(implode('', $types), ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $masuk[(int) $row['bulan']] = (int) $row['j'];
        }

        $stmt = $this->conn->prepare(
            "SELECT MONTH(tanggal) AS bulan, COUNT(*) AS j
             FROM laporan_kerusakan
             WHERE 1=1" . $where . " AND status_penanganan = 'Selesai'
             GROUP BY MONTH(tanggal)"
        );
        if ($types) {
            $stmt->bind_param(implode('', $types), ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $selesai[(int) $row['bulan']] = (int) $row['j'];
        }

        return ['masuk' => $masuk, 'selesai' => $selesai];
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
        $types  = [];
        $params = [];
        $where  = $this->periodeClause($tahun, null, $types, $params);

        $stmt = $this->conn->prepare(
            "SELECT r.id, r.nama_ruangan,
                    MONTH(lk.tanggal) AS bulan,
                    COUNT(lk.id) AS j
             FROM ruangan r
             LEFT JOIN laporan_kerusakan lk
                    ON lk.id_ruangan = r.id
                   AND lk.status_penanganan = 'Selesai'" . $where . "
             GROUP BY r.id, r.nama_ruangan, MONTH(lk.tanggal)
             ORDER BY r.nama_ruangan ASC"
        );
        if ($types) {
            $stmt->bind_param(implode('', $types), ...$params);
        }
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
}
