<?php

/**
 * Aset fisik barang di suatu ruangan: satu baris = satu unit perangkat
 * (id_ruangan + id_barang + serial_number unik + tipe/model).
 *
 * Aset tidak dihapus permanen: "hapus" = aktif = 0 (soft-delete) supaya laporan
 * yang pernah memakainya tetap bisa ditelusuri. Serial boleh NULL bila unit
 * tidak punya nomor seri asli (MySQL mengizinkan banyak NULL pada kolom UNIQUE).
 */
class AsetBarang
{
    private $conn;

    private const SELECT_BASE =
        "SELECT a.id,
                a.id_ruangan,
                r.nama_ruangan,
                a.id_barang,
                b.nama_barang,
                a.serial_number,
                a.tipe,
                a.keterangan,
                a.aktif
         FROM aset_barang a
         INNER JOIN ruangan r ON r.id = a.id_ruangan
         INNER JOIN barang  b ON b.id = a.id_barang";

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /** @return array<int, array<string, mixed>> */
    private function mapRows($result): array
    {
        $out = [];
        while ($row = $result->fetch_assoc()) {
            $row['id']         = (int) $row['id'];
            $row['id_ruangan'] = (int) $row['id_ruangan'];
            $row['id_barang']  = (int) $row['id_barang'];
            $row['aktif']      = (int) $row['aktif'];
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Semua aset, termasuk yang nonaktif — dipakai halaman admin (Unit & Barang).
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        $stmt = $this->conn->prepare(
            self::SELECT_BASE . "
            ORDER BY a.aktif DESC, r.nama_ruangan ASC, b.nama_barang ASC, a.serial_number ASC"
        );
        $stmt->execute();
        return $this->mapRows($stmt->get_result());
    }

    /**
     * Hanya aset aktif — dipakai untuk pilihan pada form laporan/aset.
     * @return array<int, array<string, mixed>>
     */
    public function getActive(): array
    {
        $stmt = $this->conn->prepare(
            self::SELECT_BASE . "
            WHERE a.aktif = 1
            ORDER BY r.nama_ruangan ASC, b.nama_barang ASC, a.serial_number ASC"
        );
        $stmt->execute();
        return $this->mapRows($stmt->get_result());
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare(self::SELECT_BASE . " WHERE a.id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Aset aktif milik satu ruangan (untuk filter dropdown barang).
     * @return array<int, array<string, mixed>>
     */
    public function getByRuangan(int $idRuangan): array
    {
        $stmt = $this->conn->prepare(
            "SELECT a.id, a.id_barang, b.nama_barang, a.serial_number, a.tipe
             FROM aset_barang a
             INNER JOIN barang b ON b.id = a.id_barang
             WHERE a.id_ruangan = ? AND a.aktif = 1
             ORDER BY b.nama_barang ASC, a.serial_number ASC"
        );
        $stmt->bind_param("i", $idRuangan);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Serial & tipe dari aset aktif untuk pasangan ruangan + barang tertentu.
     * @return array<int, array<string, mixed>>
     */
    public function getByRuanganDanBarang(int $idRuangan, int $idBarang): array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, serial_number, tipe
             FROM aset_barang
             WHERE id_ruangan = ? AND id_barang = ? AND aktif = 1
             ORDER BY serial_number ASC"
        );
        $stmt->bind_param("ii", $idRuangan, $idBarang);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create(int $idRuangan, int $idBarang, ?string $serialNumber, ?string $tipe, ?string $keterangan)
    {
        $serialNumber = ($serialNumber === null || $serialNumber === '') ? null : $serialNumber;

        $stmt = $this->conn->prepare(
            "INSERT INTO aset_barang (id_ruangan, id_barang, serial_number, tipe, keterangan)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iisss", $idRuangan, $idBarang, $serialNumber, $tipe, $keterangan);
        return $stmt->execute();
    }

    public function update(int $id, int $idRuangan, int $idBarang, ?string $serialNumber, ?string $tipe, ?string $keterangan)
    {
        $serialNumber = ($serialNumber === null || $serialNumber === '') ? null : $serialNumber;

        $stmt = $this->conn->prepare(
            "UPDATE aset_barang
             SET id_ruangan = ?, id_barang = ?, serial_number = ?, tipe = ?, keterangan = ?
             WHERE id = ?"
        );
        $stmt->bind_param("iisssi", $idRuangan, $idBarang, $serialNumber, $tipe, $keterangan, $id);
        return $stmt->execute();
    }

    /** Aktifkan (1) / nonaktifkan (0) aset — pengganti hapus permanen. */
    public function setAktif(int $id, int $aktif): bool
    {
        $aktif = $aktif ? 1 : 0;
        $stmt = $this->conn->prepare("UPDATE aset_barang SET aktif = ? WHERE id = ?");
        $stmt->bind_param("ii", $aktif, $id);
        return $stmt->execute();
    }

    /** Hapus permanen — hanya untuk baris nonaktif yang tidak dipakai laporan. */
    public function delete(int $id)
    {
        $stmt = $this->conn->prepare("DELETE FROM aset_barang WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
