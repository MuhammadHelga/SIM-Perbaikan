<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Barang.php';

class BarangController
{
    private $barang;

    public function __construct($conn)
    {
        $this->barang = new Barang($conn);
    }

    public function index()
    {
        return $this->barang->getAll();
    }

    public function store($nama_barang)
    {
        return $this->barang->tambah($nama_barang);
    }
}