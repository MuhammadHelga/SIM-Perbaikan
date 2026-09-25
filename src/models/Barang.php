<?php

class Barang
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
            "SELECT * FROM barang ORDER BY nama_barang ASC"
        );

        $stmt->execute();

        return $stmt->get_result();
    }

    // READ BY ID
    public function getById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM barang WHERE id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // CREATE
    public function create($kode_barang, $nama_barang)
    {
        $kode = ($kode_barang === '' ? null : $kode_barang);

        $stmt = $this->conn->prepare(
            "INSERT INTO barang (kode_barang, nama_barang) VALUES (?, ?)"
        );

        $stmt->bind_param("ss", $kode, $nama_barang);

        return $stmt->execute();
    }

    // UPDATE
    public function update($id, $kode_barang, $nama_barang)
    {
        $kode = ($kode_barang === '' ? null : $kode_barang);

        $stmt = $this->conn->prepare(
            "UPDATE barang
             SET kode_barang = ?, nama_barang = ?
             WHERE id = ?"
        );

        $stmt->bind_param("ssi", $kode, $nama_barang, $id);

        return $stmt->execute();
    }

    // DELETE
    public function delete($id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM barang WHERE id = ?"
        );

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}
