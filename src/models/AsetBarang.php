<?php

/**
 * Aset fisik barang di suatu ruangan: satu baris = satu unit perangkat
 * (id_ruangan + id_barang + serial_number unik + tipe/model).
 */
class AsetBarang
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Semua aset lengkap dengan nama ruangan & barang.
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        $out = [];
        $stmt = $this->conn->prepare(
            "SELECT a.id,
                    a.id_ruangan,
                    r.nama_ruangan,
                    a.id_barang,
                    b.nama_barang,
                    a.serial_number,
                    a.tipe,
                    a.keterangan
             FROM aset_barang a
             INNER JOIN ruangan r ON r.id = a.id_ruangan
             INNER JOIN barang  b ON b.id = a.id_barang
             ORDER BY r.nama_ruangan ASC, b.nama_barang ASC, a.serial_number ASC"
        );
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $row['id']         = (int) $row['id'];
            $row['id_ruangan'] = (int) $row['id_ruangan'];
            $row['id_barang']  = (int) $row['id_barang'];
            $out[] = $row;
        }
        return $out;
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT a.id,
                    a.id_ruangan,
                    r.nama_ruangan,
                    a.id_barang,
                    b.nama_barang,
                    a.serial_number,
                    a.tipe,
                    a.keterangan
             FROM aset_barang a
             INNER JOIN ruangan r ON r.id = a.id_ruangan
             INNER JOIN barang  b ON b.id = a.id_barang
             WHERE a.id = ?
             LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Aset milik satu ruangan (untuk filter dropdown barang).
     * @return array<int, array<string, mixed>>
     */
    public function getByRuangan(int $idRuangan): array
    {
        $stmt = $this->conn->prepare(
            "SELECT a.id, a.id_barang, b.nama_barang, a.serial_number, a.tipe
             FROM aset_barang a
             INNER JOIN barang b ON b.id = a.id_barang
             WHERE a.id_ruangan = ?
             ORDER BY b.nama_barang ASC, a.serial_number ASC"
        );
        $stmt->bind_param("i", $idRuangan);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Serial & tipe dari aset untuk pasangan ruangan + barang tertentu.
     * @return array<int, array<string, mixed>>
     */
    public function getByRuanganDanBarang(int $idRuangan, int $idBarang): array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, serial_number, tipe
             FROM aset_barang
             WHERE id_ruangan = ? AND id_barang = ?
             ORDER BY serial_number ASC"
        );
        $stmt->bind_param("ii", $idRuangan, $idBarang);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Cek 1 baris aset cocok dengan kombinasi ruangan + barang.
     */
    public function belongsTo(array $aset, int $idRuangan, int $idBarang): bool
    {
        return (int) ($aset['id_ruangan'] ?? 0) === $idRuangan
            && (int) ($aset['id_barang'] ?? 0) === $idBarang;
    }

    public function findByIdBaru(int $id)
    {
        $stmt = $this->conn->prepare(
            "SELECT id, id_ruangan, id_barang, serial_number, tipe
             FROM aset_barang
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create(int $idRuangan, int $idBarang, string $serialNumber, ?string $tipe, ?string $keterangan)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO aset_barang (id_ruangan, id_barang, serial_number, tipe, keterangan)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iisss", $idRuangan, $idBarang, $serialNumber, $tipe, $keterangan);
        return $stmt->execute();
    }

    public function update(int $id, int $idRuangan, int $idBarang, string $serialNumber, ?string $tipe, ?string $keterangan)
    {
        $stmt = $this->conn->prepare(
            "UPDATE aset_barang
             SET id_ruangan = ?, id_barang = ?, serial_number = ?, tipe = ?, keterangan = ?
             WHERE id = ?"
        );
        $stmt->bind_param("iisssi", $idRuangan, $idBarang, $serialNumber, $tipe, $keterangan, $id);
        return $stmt->execute();
    }

    public function delete(int $id)
    {
        $stmt = $this->conn->prepare("DELETE FROM aset_barang WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}