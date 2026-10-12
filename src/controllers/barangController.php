<?php

require_once __DIR__ . '/../models/Barang.php';

class barangController
{
    private $barang;

    public function __construct($conn)
    {
        $this->barang = new Barang($conn);
    }

    /** Barang aktif — untuk dropdown form. */
    public function index()
    {
        return $this->barang->getActive();
    }

    /** Semua barang, termasuk nonaktif — untuk halaman admin. */
    public function indexAll()
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

    /** Soft-delete: nonaktifkan barang. */
    public function destroy($id)
    {
        return $this->barang->setAktif($id, 0);
    }

    /** Aktifkan kembali barang nonaktif. */
    public function restore($id)
    {
        return $this->barang->setAktif($id, 1);
    }

    /** Hapus permanen (hanya untuk data tanpa rujukan aset/laporan). */
    public function destroyPermanent($id)
    {
        return $this->barang->delete($id);
    }
}
