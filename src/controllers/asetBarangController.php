<?php

require_once __DIR__ . '/../models/AsetBarang.php';

class asetBarangController
{
    private $aset;

    public function __construct($conn)
    {
        $this->aset = new AsetBarang($conn);
    }

    /** Aset aktif — untuk dropdown form laporan/aset. */
    public function index()
    {
        return $this->aset->getActive();
    }

    /** Semua aset, termasuk nonaktif — untuk halaman admin Unit & Barang. */
    public function indexAll()
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

    public function store(int $idRuangan, int $idBarang, ?string $serialNumber, ?string $tipe, ?string $keterangan)
    {
        return $this->aset->create($idRuangan, $idBarang, $serialNumber, $tipe, $keterangan);
    }

    public function update(int $id, int $idRuangan, int $idBarang, ?string $serialNumber, ?string $tipe, ?string $keterangan)
    {
        return $this->aset->update($id, $idRuangan, $idBarang, $serialNumber, $tipe, $keterangan);
    }

    /** Soft-delete: aset dinonaktifkan agar laporan yang memakainya tetap utuh. */
    public function destroy(int $id)
    {
        return $this->aset->setAktif($id, 0);
    }

    /** Kembalikan aset nonaktif menjadi aktif lagi. */
    public function restore(int $id)
    {
        return $this->aset->setAktif($id, 1);
    }

    /** Hapus permanen (dipakai hanya untuk aset nonaktif tanpa rujukan laporan). */
    public function destroyPermanent(int $id)
    {
        return $this->aset->delete($id);
    }
}