<?php

require_once __DIR__ . '/../models/LaporanKerusakan.php';

class laporankerusakanController
{
    private $laporankerusakan;

    public function __construct($conn)
    {
        $this->laporankerusakan = new LaporanKerusakan($conn);
    }

    public function show($id)
    {
        return $this->laporankerusakan->getById($id);
    }

    public function getHistory(int $id): array
    {
        return $this->laporankerusakan->getHistory($id);
    }

    public function recordHistory(int $id, ?int $userId, string $actorName, string $action, string $detail): bool
    {
        return $this->laporankerusakan->recordHistory($id, $userId, $actorName, $action, $detail);
    }

    public function store(
        $id_barang,
        $id_ruangan,
        $jenis_poli,
        $tanggal,
        $waktu_kejadian,
        $serial_number,
        $rincian_kerusakan,
        $uraian_kegiatan,
        $status_penanganan,
        $prioritas = 'Sedang',
        $id_user = null
    ) {
        return $this->laporankerusakan->create(
            $id_barang,
            $id_ruangan,
            $jenis_poli,
            $tanggal,
            $waktu_kejadian,
            $serial_number,
            $rincian_kerusakan,
            $uraian_kegiatan,
            $status_penanganan,
            $prioritas,
            $id_user
        );
    }

    public function update(
        $id,
        $id_barang,
        $id_ruangan,
        $jenis_poli,
        $tanggal,
        $waktu_kejadian,
        $serial_number,
        $rincian_kerusakan,
        $uraian_kegiatan,
        $status_penanganan,
        $prioritas = 'Sedang'
    ) {
        return $this->laporankerusakan->update(
            $id,
            $id_barang,
            $id_ruangan,
            $jenis_poli,
            $tanggal,
            $waktu_kejadian,
            $serial_number,
            $rincian_kerusakan,
            $uraian_kegiatan,
            $status_penanganan,
            $prioritas
        );
    }

    public function destroy($id)
    {
        return $this->laporankerusakan->delete($id);
    }

    // ===== ALUR KIRIM / TERIMA =====

    public function kirim($id, $tgl)
    {
        return $this->laporankerusakan->kirim($id, $tgl);
    }

    public function terima($id, $tgl)
    {
        return $this->laporankerusakan->terima($id, $tgl);
    }

    public function simpanSurat($id, $nama, $jabatan, $nomor, $tglSurat)
    {
        return $this->laporankerusakan->simpanSurat($id, $nama, $jabatan, $nomor, $tglSurat);
    }
}
