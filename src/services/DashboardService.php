<?php

require_once __DIR__ . '/../models/LaporanKerusakan.php';

class DashboardService
{
    private $laporan;

    public function __construct($conn)
    {
        $this->laporan = new LaporanKerusakan($conn);
    }

    /**
     * Daftar tahun yang tersedia (dari data + tahun berjalan).
     *
     * @return int[]
     */
    public function getTahunTersedia(): array
    {
        return $this->laporan->tahunTersedia();
    }

    /**
     * Kumpulkan semua angka & data grafik untuk halaman Dashboard pada tahun tertentu.
     *
     * @param int $tahun Tahun yang dipilih (default tahun berjalan)
     */
    public function getData(int $tahun): array
    {
        $total     = $this->laporan->countAll($tahun);
        $selesai   = $this->laporan->countSelesai($tahun);
        $totalLalu = $this->laporan->countAll($tahun - 1);

        $delta = $totalLalu > 0
            ? (int) round(($total - $totalLalu) / $totalLalu * 100)
            : null;

        // Grafik lintas tahun: 5 tahun terakhir sampai tahun yang dipilih (tahun-4 .. tahun).
        $tahunNaik = range($tahun - 4, $tahun);

        return [
            'total'           => $total,
            'dalam'           => $this->laporan->countDalamPenanganan($tahun),
            'selesai'         => $selesai,
            'kritis'          => $this->laporan->countKritis($tahun),
            'solveRate'       => $total > 0 ? (int) round($selesai / $total * 100) : 0,
            'delta'           => $delta,
            'tahun'           => $tahun,
            'tahunLalu'       => $tahun - 1,
            'monthly'         => $this->laporan->monthlyRecap($tahun),
            'byBarang'        => $this->laporan->countByBarang($tahun, null),
            'monthlyByBarang' => $this->laporan->monthlyByBarang($tahun),
            'byBarangPerTahun' => $this->laporan->countByBarangPerTahun($tahunNaik),
        ];
    }
}
