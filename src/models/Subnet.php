<?php

class Subnet
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return array<int, array<string, mixed>> kolom: id, cidr, prefix, gateway, mask, keterangan
     */
    public function getAll(): array
    {
        $out = [];
        $res = $this->conn->query(
            "SELECT id, cidr, prefix, gateway, mask, keterangan
             FROM subnet
             ORDER BY prefix ASC"
        );
        while ($row = $res->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $out[] = $row;
        }
        return $out;
    }

    public function getByCidr($cidr)
    {
        $stmt = $this->conn->prepare(
            "SELECT id, cidr, prefix, gateway, mask, keterangan
             FROM subnet
             WHERE cidr = ?
             LIMIT 1"
        );
        $stmt->bind_param("s", $cidr);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT id, cidr, prefix, gateway, mask, keterangan
             FROM subnet
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($cidr, $prefix, $gateway, $mask, $keterangan)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO subnet (cidr, prefix, gateway, mask, keterangan)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssss", $cidr, $prefix, $gateway, $mask, $keterangan);
        return $stmt->execute();
    }

    public function update($id, $cidr, $prefix, $gateway, $mask, $keterangan)
    {
        $stmt = $this->conn->prepare(
            "UPDATE subnet
             SET cidr = ?, prefix = ?, gateway = ?, mask = ?, keterangan = ?
             WHERE id = ?"
        );
        $stmt->bind_param("sssssi", $cidr, $prefix, $gateway, $mask, $keterangan, $id);
        return $stmt->execute();
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM subnet WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
