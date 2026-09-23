<?php

class Barang
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $query = "SELECT * FROM barang";
        return mysqli_query($this->conn, $query);
    }

    public function tambah($nama_barang)
    {
        $query = "INSERT INTO barang (nama_barang) VALUES ('$nama_barang')";
        return mysqli_query($this->conn, $query);
    }
}