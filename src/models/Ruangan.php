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
        $query = "SELECT * FROM ruangan";
        return mysqli_query($this->conn, $query);
    }

    public function tambah($nama_ruangan)
    {
        $query = "INSERT INTO barang (nama_ruangan) VALUES ('$nama_ruangan')";
        return mysqli_query($this->conn, $query);
    }
}