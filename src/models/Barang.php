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
            "SELECT * FROM barang ORDER BY id DESC"
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
    public function create($nama_barang)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO barang (nama_barang) VALUES (?)"
        );

  //  
        $stmt->bind_param("s", $nama_barang);

        return $stmt->execute();
    }

    // UPDATE
    public function update($id, $nama_barang)
    {
        $stmt = $this->conn->prepare(
            "UPDATE barang
             SET nama_barang = ?
             WHERE id = ?"
        );

        $stmt->bind_param("si", $nama_barang, $id);

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
