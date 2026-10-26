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

    public function recordHistory(
        int $id,
        ?int $userId,
        string $actorName,
        string $action,
        string $detail,
        ?string $statusLama = null,
        ?string $statusBaru = null
    ): bool {
        return $this->laporankerusakan->recordHistory(
            $id,
            $userId,
            $actorName,
            $action,
            $detail,
            $statusLama,
            $statusBaru
        );
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
        $id_user = null,
        $tipe_barang = null,
        $id_aset = null
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
            $id_user,
            $tipe_barang,
            $id_aset
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
        $prioritas = 'Sedang',
        $tipe_barang = null,
        $id_aset = null
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
            $prioritas,
            $tipe_barang,
            $id_aset
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

    // ===== ALUR ACC KABAG =====

    public function acc(int $idLaporan, int $idUser): array
    {
        return $this->laporankerusakan->acc($idLaporan, $idUser);
    }
}
