<?php

class LaporanKerusakan
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // READ
    public function getAll()
    {
        $stmt = $this->conn->prepare(
            "SELECT
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
            FROM laporankerusakan lk
            INNER JOIN barang  b ON lk.id_barang  = b.id
            INNER JOIN ruangan r ON lk.id_ruangan = r.id
            ORDER BY lk.tanggal DESC, lk.id DESC"
        );

        $stmt->execute();

        return $stmt->get_result();
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
             FROM laporankerusakan lk
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
            "INSERT INTO laporankerusakan
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
            "UPDATE laporankerusakan
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
            "DELETE FROM laporankerusakan
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
            "UPDATE laporankerusakan
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
            "UPDATE laporankerusakan
             SET
                kirim_status = 'diterima',
                tgl_terima = ?
             WHERE id = ?"
        );

        $stmt->bind_param("si", $tgl, $id);

        return $stmt->execute();
    }

    /**
     * Ubah status penanganan secara manual.
     * Bila diubah ke 'Selesai' sementara barang masih 'dikirim',
     * otomatis ditandai diterima + tgl_terima diisi.
     */
    public function updateStatus($id, $status)
    {
        $allowed = ['Pending', 'Proses', 'Selesai'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        if ($status === 'Selesai') {
            // urutan evaluasi penting: isi tgl_terima dulu sebelum kirim_status diubah
            $stmt = $this->conn->prepare(
                "UPDATE laporankerusakan
                 SET
                    status_penanganan = 'Selesai',
                    tgl_terima = IF(kirim_status = 'dikirim' AND tgl_terima IS NULL, CURDATE(), tgl_terima),
                    kirim_status = IF(kirim_status = 'dikirim', 'diterima', kirim_status)
                 WHERE id = ?"
            );

            $stmt->bind_param("i", $id);
        } else {
            $stmt = $this->conn->prepare(
                "UPDATE laporankerusakan
                 SET status_penanganan = ?
                 WHERE id = ?"
            );

            $stmt->bind_param("si", $status, $id);
        }

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

    public function countAll(): int
    {
        $res = $this->conn->query("SELECT COUNT(*) AS j FROM laporankerusakan");
        return (int) $res->fetch_assoc()['j'];
    }

    public function countDalamPenanganan(): int
    {
        $res = $this->conn->query(
            "SELECT COUNT(*) AS j FROM laporankerusakan
             WHERE status_penanganan IN ('Pending','Proses')"
        );
        return (int) $res->fetch_assoc()['j'];
    }

    public function countSelesai(): int
    {
        $res = $this->conn->query(
            "SELECT COUNT(*) AS j FROM laporankerusakan
             WHERE status_penanganan = 'Selesai'"
        );
        return (int) $res->fetch_assoc()['j'];
    }

    public function countKritis(): int
    {
        $res = $this->conn->query(
            "SELECT COUNT(*) AS j FROM laporankerusakan
             WHERE prioritas = 'Tinggi' AND status_penanganan <> 'Selesai'"
        );
        return (int) $res->fetch_assoc()['j'];
    }

    public function countPeriode(int $tahun, ?int $bulan = null): int
    {
        $sql = "SELECT COUNT(*) AS j FROM laporankerusakan WHERE YEAR(tanggal) = ?";
        $stmt = $this->conn->prepare($sql . ($bulan !== null ? " AND MONTH(tanggal) = ?" : ""));

        if ($bulan !== null) {
            $stmt->bind_param('ii', $tahun, $bulan);
        } else {
            $stmt->bind_param('i', $tahun);
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

        $stmt = $this->conn->prepare(
            "SELECT MONTH(tanggal) AS bulan, COUNT(*) AS j
             FROM laporankerusakan
             WHERE YEAR(tanggal) = ?
             GROUP BY MONTH(tanggal)"
        );
        $stmt->bind_param('i', $tahun);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $masuk[(int) $row['bulan']] = (int) $row['j'];
        }

        $stmt = $this->conn->prepare(
            "SELECT MONTH(tanggal) AS bulan, COUNT(*) AS j
             FROM laporankerusakan
             WHERE YEAR(tanggal) = ? AND status_penanganan = 'Selesai'
             GROUP BY MONTH(tanggal)"
        );
        $stmt->bind_param('i', $tahun);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $selesai[(int) $row['bulan']] = (int) $row['j'];
        }

        return ['masuk' => $masuk, 'selesai' => $selesai];
    }

    /**
     * Jumlah laporan per barang, urut terbanyak.
     * @return array<int, array{nama:string, jumlah:int}>
     */
    public function countByBarang(): array
    {
        $out = [];
        $res = $this->conn->query(
            "SELECT b.nama_barang AS nama, COUNT(*) AS jumlah
             FROM laporankerusakan lk
             INNER JOIN barang b ON lk.id_barang = b.id
             GROUP BY lk.id_barang, b.nama_barang
             ORDER BY jumlah DESC"
        );
        while ($row = $res->fetch_assoc()) {
            $out[] = ['nama' => $row['nama'], 'jumlah' => (int) $row['jumlah']];
        }
        return $out;
    }

    /**
     * Daftar tahun yang punya data laporan (+ tahun berjalan), urut menurun.
     * @return int[]
     */
    public function tahunTersedia(): array
    {
        $years = [];
        $res = $this->conn->query(
            "SELECT DISTINCT YEAR(tanggal) AS th FROM laporankerusakan ORDER BY th DESC"
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
        $stmt = $this->conn->prepare(
            "SELECT r.id, r.nama_ruangan,
                    MONTH(lk.tanggal) AS bulan,
                    COUNT(lk.id) AS j
             FROM ruangan r
             LEFT JOIN laporankerusakan lk
                    ON lk.id_ruangan = r.id
                   AND lk.status_penanganan = 'Selesai'
                   AND YEAR(lk.tanggal) = ?
             GROUP BY r.id, r.nama_ruangan, MONTH(lk.tanggal)
             ORDER BY r.nama_ruangan ASC"
        );
        $stmt->bind_param('i', $tahun);
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
