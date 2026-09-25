<?php

require_once __DIR__ . '/../models/Barang.php';

class barangController
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

    public function show($id)
    {
        return $this->barang->getById($id);
    }

    public function store($kode_barang, $nama_barang)
    {
        return $this->barang->create($kode_barang, $nama_barang);
    }

    public function update($id, $kode_barang, $nama_barang)
    {
        return $this->barang->update($id, $kode_barang, $nama_barang);
    }

    public function destroy($id)
    {
        return $this->barang->delete($id);
    }
}
