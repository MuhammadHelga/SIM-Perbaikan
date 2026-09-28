<?php

class AlokasiIp
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return array<int, array<string, mixed>> kolom: id, subnet, unit, lokasi, hostname, host_octet, status
     */
    public function getAll(): array
    {
        $out = [];
        $res = $this->conn->query(
            "SELECT id, subnet, unit, lokasi, hostname, host_octet, status
             FROM alokasi_ip
             ORDER BY host_octet ASC"
        );
        while ($row = $res->fetch_assoc()) {
            $row['id']         = (int) $row['id'];
            $row['host_octet'] = (int) $row['host_octet'];
            $out[] = $row;
        }
        return $out;
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT id, subnet, unit, lokasi, hostname, host_octet, status
             FROM alokasi_ip
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($subnet, $unit, $lokasi, $hostname, $host_octet, $status = 'online')
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO alokasi_ip (subnet, unit, lokasi, hostname, host_octet, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssis", $subnet, $unit, $lokasi, $hostname, $host_octet, $status);
        return $stmt->execute();
    }

    public function update($id, $subnet, $unit, $lokasi, $hostname, $host_octet, $status = 'online')
    {
        $stmt = $this->conn->prepare(
            "UPDATE alokasi_ip
             SET subnet = ?, unit = ?, lokasi = ?, hostname = ?, host_octet = ?, status = ?
             WHERE id = ?"
        );
        $stmt->bind_param("ssssisi", $subnet, $unit, $lokasi, $hostname, $host_octet, $status, $id);
        return $stmt->execute();
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM alokasi_ip WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
