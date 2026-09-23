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
                lk.*,
                b.nama_barang,
                r.nama_ruangan
            FROM laporankerusakan lk
            INNER JOIN barang b ON lk.id_barang = b.id
            INNER JOIN ruangan r ON lk.id_ruangan = r.id
            ORDER BY lk.id DESC"
        );

        $stmt->execute();

        return $stmt->get_result();
    }

    // READ BY ID
    public function getById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT *
             FROM laporankerusakan
             WHERE id = ?"
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
        $rincian_kerusakan,
        $uraian_kegiatan,
        $status_penanganan
    ) {
        $stmt = $this->conn->prepare(
            "INSERT INTO laporankerusakan
            (
                id_barang,
                id_ruangan,
                tanggal,
                rincian_kerusakan,
                uraian_kegiatan,
                status_penanganan
            )
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iissss",
            $id_barang,
            $id_ruangan,
            $tanggal,
            $rincian_kerusakan,
            $uraian_kegiatan,
            $status_penanganan
        );

        return $stmt->execute();
    }

    // UPDATE
    public function update(
        $id,
        $id_barang,
        $id_ruangan,
        $tanggal,
        $rincian_kerusakan,
        $uraian_kegiatan,
        $status_penanganan
    ) {
        $stmt = $this->conn->prepare(
            "UPDATE laporankerusakan
             SET
                id_barang = ?,
                id_ruangan = ?,
                tanggal = ?,
                rincian_kerusakan = ?,
                uraian_kegiatan = ?,
                status_penanganan = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "iissssi",
            $id_barang,
            $id_ruangan,
            $tanggal,
            $rincian_kerusakan,
            $uraian_kegiatan,
            $status_penanganan,
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
