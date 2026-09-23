<?php

require_once __DIR__ . '/../models/Barang.php';

class barangControllerarangController
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

    public function store($nama_barang)
    {
        return $this->barang->create($nama_barang);
    }

    public function update($id, $nama_barang)
    {
        return $this->barang->update($id, $nama_barang);
    }

    public function destroy($id)
    {
        return $this->barang->delete($id);
    }
}
