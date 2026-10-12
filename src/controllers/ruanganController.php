<?php

require_once __DIR__ . '/../models/Ruangan.php';

class ruanganController
{
    private $ruangan;

    public function __construct($conn)
    {
        $this->ruangan = new Ruangan($conn);
    }

    /** Ruangan aktif — untuk dropdown form. */
    public function index()
    {
        return $this->ruangan->getActive();
    }

    /** Semua ruangan, termasuk nonaktif — untuk halaman admin. */
    public function indexAll()
    {
        return $this->ruangan->getAll();
    }

    public function show($id)
    {
        return $this->ruangan->getById($id);
    }

    public function store($kode_ruangan, $nama_ruangan)
    {
        return $this->ruangan->create($kode_ruangan, $nama_ruangan);
    }

    public function update($id, $kode_ruangan, $nama_ruangan)
    {
        return $this->ruangan->update($id, $kode_ruangan, $nama_ruangan);
    }

    /** Soft-delete: nonaktifkan ruangan. */
    public function destroy($id)
    {
        return $this->ruangan->setAktif($id, 0);
    }

    /** Aktifkan kembali ruangan nonaktif. */
    public function restore($id)
    {
        return $this->ruangan->setAktif($id, 1);
    }

    /** Hapus permanen (hanya untuk data tanpa rujukan aset/laporan). */
    public function destroyPermanent($id)
    {
        return $this->ruangan->delete($id);
    }
}
