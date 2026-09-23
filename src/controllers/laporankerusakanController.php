<?php

require_once __DIR__ . '/../models/LaporanKerusakan.php';

class laporankerusakanController
{
    private $laporankerusakan;

    public function __construct($conn)
    {
        $this->laporankerusakan = new LaporanKerusakan($conn);
    }

    public function index()
    {
        return $this->laporankerusakan->getAll();
    }

    public function show($id)
    {
        return $this->laporankerusakan->getById($id);
    }

    public function store(
        $id_barang,
        $id_ruangan,
        $tanggal,
        $rincian_kerusakan,
        $uraian_kegiatan,
        $status_penanganan
    ) {
        return $this->laporankerusakan->create(
            $id_barang,
            $id_ruangan,
            $tanggal,
            $rincian_kerusakan,
            $uraian_kegiatan,
            $status_penanganan
        );
    }

    public function update(
        $id,
        $id_barang,
        $id_ruangan,
        $tanggal,
        $rincian_kerusakan,
        $uraian_kegiatan,
        $status_penanganan
    ) {
        return $this->laporankerusakan->update(
            $id,
            $id_barang,
            $id_ruangan,
            $tanggal,
            $rincian_kerusakan,
            $uraian_kegiatan,
            $status_penanganan
        );
    }

    public function destroy($id)
    {
        return $this->laporankerusakan->delete($id);
    }

    public function getBarang()
    {
        return $this->laporankerusakan->getBarang();
    }

    public function getRuangan()
    {
        return $this->laporankerusakan->getRuangan();
    }
}
