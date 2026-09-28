<?php

/**
 * Logika halaman Jaringan & Alokasi IP:
 * menghitung IP penuh, filter, paginasi, serta statistik/okupansi host.
 */
class JaringanService
{
    private const PER_PAGE      = 9;
    private const HOST_CAPACITY = 245; // /24, host valid 10-254
    private const CORE_RESERVED = 9;

    /**
     * @param array<int, array<string,mixed>> $subnets     hasil subnetController->index()
     * @param array<int, array<string,mixed>> $allocations hasil alokasiIpController->index()
     * @param array{subnet?:string,search?:string,unit?:string,status?:string,page?:int} $filters
     * @return array<string,mixed> seluruh variabel yang dibutuhkan JaringanView
     */
    public function build(array $subnets, array $allocations, array $filters): array
    {
        $selectedSubnet = trim((string) ($filters['subnet'] ?? ''));
        $search         = (string) ($filters['search'] ?? '');
        $filterUnit     = (string) ($filters['unit'] ?? '');
        $filterStatus   = (string) ($filters['status'] ?? '');
        $page           = max(1, (int) ($filters['page'] ?? 1));

        // Peta prefix per CIDR.
        $prefixByCidr = [];
        foreach ($subnets as $s) {
            $prefixByCidr[$s['cidr']] = $s['prefix'];
        }

        // Hitung IP penuh (prefix + oktet host) untuk tiap baris.
        foreach ($allocations as $i => $r) {
            $prefix = $prefixByCidr[$r['subnet']]
                ?? implode('.', array_slice(explode('.', $r['subnet']), 0, 3));
            $allocations[$i]['ip'] = $prefix . '.' . (int) $r['host_octet'];
        }
        $komputerListAll = $allocations;

        // Subnet terpilih untuk header (null = "Semua Subnet").
        $subnet = null;
        if ($selectedSubnet !== '') {
            foreach ($subnets as $s) {
                if ($s['cidr'] === $selectedSubnet) {
                    $subnet = $s;
                    break;
                }
            }
        }

        $unitOptions = array_values(array_unique(array_column($komputerListAll, 'unit')));

        $filtered = array_values(array_filter(
            $komputerListAll,
            function ($r) use ($search, $selectedSubnet, $filterUnit, $filterStatus) {
                if ($selectedSubnet !== '' && $r['subnet'] !== $selectedSubnet) {
                    return false;
                }
                if ($filterUnit !== '' && $r['unit'] !== $filterUnit) {
                    return false;
                }
                if ($filterStatus !== '' && $r['status'] !== $filterStatus) {
                    return false;
                }
                if ($search !== '') {
                    $keyword  = mb_strtolower($search);
                    $haystack = mb_strtolower($r['unit'] . ' ' . $r['hostname'] . ' ' . $r['host_octet'] . ' ' . $r['ip']);
                    if (!str_contains($haystack, $keyword)) {
                        return false;
                    }
                }
                return true;
            }
        ));

        $totalRows  = count($filtered);
        $totalPages = max(1, (int) ceil($totalRows / self::PER_PAGE));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $komputerList = array_slice($filtered, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        // Statistik & okupansi dari seluruh data subnet terpilih (bukan hasil search).
        $scoped = $selectedSubnet !== ''
            ? array_values(array_filter($komputerListAll, fn($r) => $r['subnet'] === $selectedSubnet))
            : $komputerListAll;

        $occupancyMap = [];
        foreach ($scoped as $r) {
            $occupancyMap[(int) $r['host_octet']] = $r['status'];
        }

        $terisi    = count($scoped);
        $offline   = count(array_filter($scoped, fn($r) => $r['status'] === 'offline'));
        $online    = $terisi - $offline;
        $kapasitas = self::HOST_CAPACITY;
        $kosong    = max(0, $kapasitas - $terisi);

        $occupancy = [
            'core'            => self::CORE_RESERVED,
            'terisi'          => $terisi,
            'kosong'          => $kosong,
            'tersedia_persen' => round(($kosong / $kapasitas) * 100, 1),
            'map'             => $occupancyMap,
        ];

        $stats = [
            'total_unit'     => $terisi,
            'ip_terpakai'    => $terisi,
            'host_kosong'    => $kosong,
            'uptime_percent' => $terisi > 0 ? round(($online / $terisi) * 100, 1) : 100,
            'online'         => $online,
            'offline'        => $offline,
        ];

        return [
            'subnets'        => $subnets,
            'selectedSubnet' => $selectedSubnet,
            'subnet'         => $subnet,
            'subnetCount'    => count($subnets),
            'unitOptions'    => $unitOptions,
            'komputerList'   => $komputerList,
            'occupancy'      => $occupancy,
            'stats'          => $stats,
            'search'         => $search,
            'filterUnit'     => $filterUnit,
            'filterStatus'   => $filterStatus,
            'page'           => $page,
            'perPage'        => self::PER_PAGE,
            'totalRows'      => $totalRows,
        ];
    }
}
