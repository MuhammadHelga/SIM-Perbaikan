<?php

require_once __DIR__ . '/../models/LaporanKerusakan.php';

class RuangService
{
    private $laporan;

    public function __construct($conn)
    {
        $this->laporan = new LaporanKerusakan($conn);
    }

    public function getTahunTersedia(): array
    {
        return $this->laporan->tahunTersedia();
    }

    /**
     * Data untuk halaman Rekap Ruang.
     *
     * @param int    $tahun Tahun rekap
     * @param string $q     Kata kunci nama ruangan (opsional)
     * @param string $sort  'asc' (terendah) atau 'desc' (tertinggi)
     */
    public function getData(int $tahun, string $q, string $sort): array
    {
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $rows          = $this->laporan->recapSelesaiByRuangan($tahun);
        $jumlahRuangan = count($rows);

        // --- Kartu ringkasan (dari data tahun tsb, tanpa filter pencarian) ---
        $topUnit = null;
        foreach ($rows as $row) {
            if ($topUnit === null || $row['total'] > $topUnit['total']) {
                $topUnit = ['nama' => $row['nama'], 'total' => $row['total']];
            }
        }

        $totalPerBulanTahun = array_fill(1, 12, 0);
        foreach ($rows as $row) {
            foreach ($row['bulan'] as $b => $v) {
                $totalPerBulanTahun[$b] += $v;
            }
        }

        $peakTotal = 0;
        foreach ($totalPerBulanTahun as $v) {
            if ($v > $peakTotal) {
                $peakTotal = $v;
            }
        }

        $peakMonths = [];
        if ($peakTotal > 0) {
            foreach ($totalPerBulanTahun as $b => $v) {
                if ($v === $peakTotal) {
                    $peakMonths[] = $namaBulan[$b];
                }
            }
        }

        // --- Pencarian nama ruangan ---
        if ($q !== '') {
            $rows = array_values(array_filter(
                $rows,
                fn($r) => stripos($r['nama'], $q) !== false
            ));
        }

        // --- Urut berdasarkan total ---
        usort($rows, function ($a, $b) use ($sort) {
            if ($a['total'] === $b['total']) {
                return strcmp($a['nama'], $b['nama']);
            }
            return $sort === 'asc'
                ? ($a['total'] < $b['total'] ? -1 : 1)
                : ($a['total'] > $b['total'] ? -1 : 1);
        });

        // --- Total per bulan dari baris yang ditampilkan ---
        $totalPerBulan = array_fill(1, 12, 0);
        foreach ($rows as $row) {
            foreach ($row['bulan'] as $b => $v) {
                $totalPerBulan[$b] += $v;
            }
        }

        return [
            'tahun'         => $tahun,
            'q'             => $q,
            'sort'          => $sort,
            'rows'          => $rows,
            'totalPerBulan' => $totalPerBulan,
            'topUnit'       => $topUnit,
            'peakMonths'    => $peakMonths,
            'peakTotal'     => $peakTotal,
            'jumlahRuangan' => $jumlahRuangan,
        ];
    }
}
