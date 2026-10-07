<?php
/**
 * Rekap cetak: semua baris sesuai filter (tanpa paginasi).
 * Dibuka dari tombol "Cetak Rekap Laporan" (tab baru) dan otomatis memicu print.
 *
 * @var array<int, array<string,mixed>> $laporanList
 * @var array{total:int,pending:int,selesai:int} $stats
 * @var string $periode
 * @var string $statusFilter
 * @var string $search
 */
$laporanList  = $laporanList ?? [];
$stats        = $stats ?? ['total' => 0, 'pending' => 0, 'selesai' => 0];
$periode      = $periode ?? '';
$statusFilter = $statusFilter ?? '';
$search       = $search ?? '';

$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
$periodeLabel = 'Semua Periode';
if ($periode !== '') {
    $periodeLabel = $namaBulan[(int) substr($periode, 5, 2)] . ' ' . substr($periode, 0, 4);
}

$fmtTanggal = function ($tgl) {
    if (empty($tgl)) {
        return '-';
    }
    $ts = strtotime((string) $tgl);
    return $ts ? date('d M Y', $ts) : (string) $tgl;
};

$filterParts = [];
if ($statusFilter !== '') {
    $filterParts[] = 'Status: ' . $statusFilter;
}
if ($search !== '') {
    $filterParts[] = 'Cari: ' . $search;
}
$filterLabel = $filterParts ? implode(' · ', $filterParts) : 'Tanpa filter tambahan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Laporan - <?= htmlspecialchars($periodeLabel) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #111;
            background: #eef1f7;
            padding: 20px;
        }
        .sheet {
            background: #fff;
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 28px;
            box-shadow: 0 10px 30px rgba(0, 40, 142, 0.12);
        }
        .toolbar {
            max-width: 1100px;
            margin: 0 auto 12px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .toolbar button {
            font: inherit;
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #00288e;
            background: #00288e;
            color: #fff;
            cursor: pointer;
        }
        .toolbar button.ghost {
            background: #fff;
            color: #00288e;
        }
        .head { text-align: center; border-bottom: 2px solid #00288e; padding-bottom: 12px; margin-bottom: 12px; }
        .head h1 { margin: 0; font-size: 18px; color: #00288e; }
        .head h2 { margin: 4px 0 0; font-size: 14px; font-weight: 600; }
        .meta { margin-top: 6px; font-size: 12px; color: #444; }
        .meta span { display: inline-block; margin: 0 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #cfd6e6; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #00288e; color: #fff; font-weight: 600; }
        tbody tr:nth-child(even) { background: #f5f7fc; }
        .nowrap { white-space: nowrap; }
        .center { text-align: center; }
        .empty { text-align: center; padding: 24px; color: #666; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; max-width: none; padding: 0; }
            .toolbar { display: none !important; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="ghost" onclick="window.close()">Tutup</button>
        <button type="button" onclick="window.print()">Cetak</button>
    </div>

    <div class="sheet">
        <div class="head">
            <h1>SIM-Perbaikan &mdash; RS Al-Huda</h1>
            <h2>Rekap Laporan Kegiatan &amp; Kerusakan</h2>
            <div class="meta">
                <span><strong>Periode:</strong> <?= htmlspecialchars($periodeLabel) ?></span>
                <span><strong>Filter:</strong> <?= htmlspecialchars($filterLabel) ?></span>
                <span><strong>Dicetak:</strong> <?= date('d M Y H:i') ?></span>
                <span><strong>Total:</strong> <?= (int) $stats['total'] ?></span>
                <span><strong>Pending:</strong> <?= (int) $stats['pending'] ?></span>
                <span><strong>Selesai:</strong> <?= (int) $stats['selesai'] ?></span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:34px;">No</th>
                    <th class="nowrap">Tanggal</th>
                    <th>Urusan/Ruangan</th>
                    <th>Barang</th>
                    <th>No Seri</th>
                    <th>Rincian Kerusakan</th>
                    <th>Uraian Kegiatan</th>
                    <th>Status</th>
                    <th class="nowrap">Tgl Kirim/Terima</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$laporanList): ?>
                    <tr><td class="empty" colspan="9">Tidak ada data untuk periode/filter ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($laporanList as $i => $row): ?>
                    <tr>
                        <td class="center"><?= $i + 1 ?></td>
                        <td class="nowrap"><?= htmlspecialchars($fmtTanggal($row['tanggal'])) ?></td>
                        <td><?= htmlspecialchars(trim($row['urusan'] . (!empty($row['jenis_poli']) ? ' - ' . $row['jenis_poli'] : ''))) ?></td>
                        <td><?= htmlspecialchars($row['barang']) ?></td>
                        <td><?= htmlspecialchars($row['serial_number'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['kerusakan']) ?></td>
                        <td><?= htmlspecialchars($row['uraian']) ?></td>
                        <td><?= htmlspecialchars($row['status_penanganan']) ?></td>
                        <td class="nowrap">
                            <?php if (!empty($row['tgl_kirim'])): ?>Kirim: <?= htmlspecialchars($fmtTanggal($row['tgl_kirim'])) ?><br><?php endif; ?>
                            <?php if (!empty($row['tgl_terima'])): ?>Terima: <?= htmlspecialchars($fmtTanggal($row['tgl_terima'])) ?><?php endif; ?>
                            <?php if (empty($row['tgl_kirim']) && empty($row['tgl_terima'])): ?>-<?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        window.addEventListener('load', function () { window.print(); });
    </script>
</body>
</html>
