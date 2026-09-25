<?php

class Ruangan
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM ruangan ORDER BY nama_ruangan ASC"
        );

        $stmt->execute();

        return $stmt->get_result();
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM ruangan WHERE id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function create($kode_ruangan, $nama_ruangan)
    {
        $kode = ($kode_ruangan === '' ? null : $kode_ruangan);

        $stmt = $this->conn->prepare(
            "INSERT INTO ruangan (kode_ruangan, nama_ruangan) VALUES (?, ?)"
        );

        $stmt->bind_param("ss", $kode, $nama_ruangan);

        return $stmt->execute();
    }

    public function update($id, $kode_ruangan, $nama_ruangan)
    {
        $kode = ($kode_ruangan === '' ? null : $kode_ruangan);

        $stmt = $this->conn->prepare(
            "UPDATE ruangan
             SET kode_ruangan = ?, nama_ruangan = ?
             WHERE id = ?"
        );

        $stmt->bind_param("ssi", $kode, $nama_ruangan, $id);

        return $stmt->execute();
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM ruangan WHERE id = ?"
        );

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}
