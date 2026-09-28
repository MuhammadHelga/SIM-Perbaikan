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
        $perPage = (int) ($filters['per_page'] ?? 25);
        $page    = (int) ($filters['page'] ?? 1);

        if (!in_array($perPage, [25, 50, 100, 200], true)) {
            $perPage = 25;
        }

        // Kartu statistik hanya dibatasi periode (bukan status/cari),
        // supaya angkanya tidak berubah saat tabel difilter.
        $stats = $this->laporan->statsByPeriode($periode);

        // Filter status/cari + paginasi dikerjakan di database.
        $totalRows  = $this->laporan->countFiltered($filters);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        if ($page < 1) {
            $page = 1;
        } elseif ($page > $totalPages) {
            $page = $totalPages;
        }

        $rows = $this->laporan->getListing($filters, $perPage, ($page - 1) * $perPage);

        return [
            'rows'       => $rows,
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
