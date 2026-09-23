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
        $query = "SELECT laporankerusakan.*,
        barang. nama_barang, ruangan. nama_ruangan FROM laporankerusakan JOIN barang on laporankerusakan.id_barang 
        JOIN ruangan on laporankerusakan.id_ruangan
        ";
        return mysqli_query($this->conn, $query);
    }
}