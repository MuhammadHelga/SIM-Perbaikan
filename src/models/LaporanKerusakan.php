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
}
