<?php

require_once __DIR__ . '/../models/LaporanKerusakan.php';

/**
 * Logika daftar laporan: filter periode/status/cari, kartu statistik, dan paginasi.
 * Dipisah dari router supaya public/index.php tetap tipis.
 */
class LaporanService
{
    private LaporanKerusakan $laporan;

    public function __construct(mysqli $conn)
    {
        $this->laporan = new LaporanKerusakan($conn);
    }

    /**
     * @param array{periode?:string, status?:string, search?:string, per_page?:int, page?:int} $filters
     * @return array{rows:array, stats:array{total:int,pending:int,selesai:int}, page:int, perPage:int, totalPages:int, totalRows:int}
     */
    public function getListing(array $filters): array
    {
        $periode = (string) ($filters['periode'] ?? '');
        $status  = (string) ($filters['status'] ?? '');
        $search  = (string) ($filters['search'] ?? '');
        $perPage = (int) ($filters['per_page'] ?? 25);
        $page    = (int) ($filters['page'] ?? 1);

        if (!in_array($perPage, [25, 50, 100, 200], true)) {
            $perPage = 25;
        }

        $all = $this->laporan->getAll()->fetch_all(MYSQLI_ASSOC);

        // Kartu statistik hanya dibatasi periode (bukan status/cari),
        // supaya angkanya tidak berubah saat tabel difilter.
        $period = $all;
        if ($periode !== '') {
            $period = array_values(array_filter(
                $period,
                fn($r) => substr((string) $r['tanggal'], 0, 7) === $periode
            ));
        }

        $stats = ['total' => count($period), 'pending' => 0, 'selesai' => 0];
        foreach ($period as $r) {
            if (($r['status_penanganan'] ?? '') === 'Selesai') {
                $stats['selesai']++;
            } else {
                $stats['pending']++;
            }
        }

        // Tabel: filter status + pencarian diterapkan di atas periode terpilih.
        $filtered = $period;
        if ($status !== '') {
            $filtered = array_values(array_filter(
                $filtered,
                fn($r) => $r['status_penanganan'] === $status
            ));
        }

        if ($search !== '') {
            $keyword = mb_strtolower($search);
            $filtered = array_values(array_filter($filtered, function ($r) use ($keyword) {
                return str_contains(mb_strtolower($r['urusan']), $keyword)
                    || str_contains(mb_strtolower($r['barang']), $keyword)
                    || str_contains(mb_strtolower($r['kerusakan']), $keyword)
                    || str_contains(mb_strtolower((string) ($r['serial_number'] ?? '')), $keyword);
            }));
        }

        $totalRows  = count($filtered);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        if ($page < 1) {
            $page = 1;
        } elseif ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'rows'       => array_slice($filtered, ($page - 1) * $perPage, $perPage),
            'stats'      => $stats,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalPages,
            'totalRows'  => $totalRows,
        ];
    }

    /** @return array<int, array<string,mixed>> */
    public function getRuangan(): array
    {
        return $this->laporan->getRuangan()->fetch_all(MYSQLI_ASSOC);
    }

    /** @return array<int, array<string,mixed>> */
    public function getBarang(): array
    {
        return $this->laporan->getBarang()->fetch_all(MYSQLI_ASSOC);
    }
}
