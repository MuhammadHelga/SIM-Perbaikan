<?php

class Laporan
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll()
    {
        $query = "SELECT laporan.*,
        barang. nama_barang, ruangan. nama_ruangan FROM laporan JOIN barang on laporan id_barang 
        JOIN ruangan on laporan id_ruangan
        ";
        return mysqli_query($this->conn, $query);
    }
}