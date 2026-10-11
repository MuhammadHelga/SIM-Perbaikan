<?php

require_once __DIR__ . '/../models/AsetBarang.php';

class asetBarangController
{
    private $aset;

    public function __construct($conn)
    {
        $this->aset = new AsetBarang($conn);
    }

    public function index()
    {
        return $this->aset->getAll();
    }

    public function show($id)
    {
        return $this->aset->getById($id);
    }

    public function byRuangan(int $idRuangan)
    {
        return $this->aset->getByRuangan($idRuangan);
    }

    public function byRuanganDanBarang(int $idRuangan, int $idBarang)
    {
        return $this->aset->getByRuanganDanBarang($idRuangan, $idBarang);
    }

    public function store(int $idRuangan, int $idBarang, string $serialNumber, ?string $tipe, ?string $keterangan)
    {
        return $this->aset->create($idRuangan, $idBarang, $serialNumber, $tipe, $keterangan);
    }

    public function update(int $id, int $idRuangan, int $idBarang, string $serialNumber, ?string $tipe, ?string $keterangan)
    {
        return $this->aset->update($id, $idRuangan, $idBarang, $serialNumber, $tipe, $keterangan);
    }

    public function destroy(int $id)
    {
        return $this->aset->delete($id);
    }
}