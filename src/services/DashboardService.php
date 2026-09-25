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
     * Kumpulkan semua angka & data grafik untuk halaman Dashboard.
     *
     * @param int $tahun Tahun untuk grafik tren bulanan
     */
    public function getData(int $tahun): array
    {
        $today = new DateTimeImmutable('today');
        $prev  = $today->modify('first day of last month');

        $bulanIni  = $this->laporan->countPeriode((int) $today->format('Y'), (int) $today->format('n'));
        $bulanLalu = $this->laporan->countPeriode((int) $prev->format('Y'), (int) $prev->format('n'));

        $delta = $bulanLalu > 0
            ? (int) round(($bulanIni - $bulanLalu) / $bulanLalu * 100)
            : null;

        $total   = $this->laporan->countAll();
        $selesai = $this->laporan->countSelesai();

        return [
            'total'     => $total,
            'dalam'     => $this->laporan->countDalamPenanganan(),
            'selesai'   => $selesai,
            'kritis'    => $this->laporan->countKritis(),
            'solveRate' => $total > 0 ? (int) round($selesai / $total * 100) : 0,
            'delta'     => $delta,
            'tahun'     => $tahun,
            'bulan'     => (int) $today->format('n'),
            'monthly'   => $this->laporan->monthlyRecap($tahun),
            'byBarang'  => $this->laporan->countByBarang(),
        ];
    }
}
