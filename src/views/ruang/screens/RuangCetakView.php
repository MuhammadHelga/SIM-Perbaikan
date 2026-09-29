<?php
/**
 * Cetak Rekap Ruang: tabel tunggal (header berulang tiap halaman), tanpa paginasi.
 * Dibuka dari tombol "Cetak Rekap Ruang" (tab baru) dan otomatis memicu print.
 *
 * @var array<int, array{id:int,nama:string,bulan:int[],total:int}> $rows
 * @var int[]   $totalPerBulan
 * @var array{nama:string,total:int}|null $topUnit
 * @var string[] $peakMonths
 * @var int     $peakTotal
 * @var int     $tahun
 * @var string  $q
 * @var string  $sort
 */
$rows          = $rows ?? [];
$totalPerBulan = $totalPerBulan ?? array_fill(1, 12, 0);
$topUnit       = $topUnit ?? null;
$peakMonths    = $peakMonths ?? [];
$peakTotal     = (int) ($peakTotal ?? 0);
$tahun         = (int) ($tahun ?? date('Y'));
$q             = $q ?? '';
$sort          = $sort ?? 'desc';

$bulanSingkat = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

$topUnitNama = $topUnit['nama'] ?? '—';
$peakLabel   = $peakMonths ? implode(' & ', $peakMonths) : '—';
$sortLabel   = $sort === 'asc' ? 'Total Terendah' : 'Total Tertinggi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Ruang <?= htmlspecialchars((string) $tahun) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #111;
            background: #eef1f7;
            padding: 20px;
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
        .toolbar button.ghost { background: #fff; color: #00288e; }
        .sheet {
            background: #fff;
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 28px;
            box-shadow: 0 10px 30px rgba(0, 40, 142, 0.12);
        }
        .head { text-align: center; border-bottom: 2px solid #00288e; padding-bottom: 12px; margin-bottom: 10px; }
        .head h1 { margin: 0; font-size: 18px; color: #00288e; }
        .head h2 { margin: 4px 0 0; font-size: 14px; font-weight: 600; }
        .meta { margin-top: 6px; font-size: 12px; color: #444; }
        .meta span { display: inline-block; margin: 0 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; table-layout: fixed; }
        th, td { border: 1px solid #b9c2d6; padding: 3px 4px; text-align: center; }
        th { background: #00288e; color: #fff; font-weight: 600; }
        th.unit, td.unit { text-align: left; width: 16%; }
        tbody tr:nth-child(even) { background: #f5f7fc; }
        td.cell-medium { background: #fde68a; }
        td.cell-high { background: #fca5a5; font-weight: 700; }
        tfoot td { font-weight: 700; background: #e0e4ff; }
        .empty { text-align: center; padding: 20px; color: #666; }
        .legend { margin-top: 8px; font-size: 11px; color: #444; }
        .legend .box { display: inline-block; width: 11px; height: 11px; border: 1px solid #999; vertical-align: middle; margin: 0 4px 0 10px; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; max-width: none; padding: 0; }
            .toolbar { display: none !important; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            tr { break-inside: avoid; }
            @page { size: A4 landscape; margin: 10mm; }
            html, body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
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
            <h2>Total Barang Diperbaiki per Ruang/Urusan &mdash; Tahun <?= $tahun ?></h2>
            <div class="meta">
                <span><strong>Urut:</strong> <?= htmlspecialchars($sortLabel) ?></span>
                <span><strong>Pencarian:</strong> <?= $q !== '' ? htmlspecialchars($q) : 'Semua' ?></span>
                <span><strong>Dicetak:</strong> <?= date('d M Y H:i') ?></span>
            </div>
            <div class="meta">
                <span><strong>Ruang Terbanyak:</strong> <?= htmlspecialchars($topUnitNama) ?> (<?= (int) ($topUnit['total'] ?? 0) ?>)</span>
                <span><strong>Bulan Puncak:</strong> <?= htmlspecialchars($peakLabel) ?> (<?= $peakTotal ?>)</span>
                <span><strong>Jumlah Ruangan:</strong> <?= count($rows) ?></span>
            </div>
        </div>

        <table>
            <colgroup>
                <col style="width: 16%;">
                <?php for ($b = 1; $b <= 12; $b++): ?><col style="width: 7%;"><?php endfor; ?>
            </colgroup>
            <thead>
                <tr>
                    <th class="unit">Nama Unit / Ruangan</th>
                    <?php foreach ($bulanSingkat as $bln): ?>
                        <th><?= htmlspecialchars($bln) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td class="empty" colspan="13">Belum ada data untuk tahun/pencarian ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="unit"><?= htmlspecialchars($row['nama']) ?></td>
                        <?php for ($b = 1; $b <= 12; $b++): ?>
                            <?php $v = (int) ($row['bulan'][$b] ?? 0); ?>
                            <td class="<?= $v > 10 ? 'cell-high' : ($v >= 6 ? 'cell-medium' : '') ?>"><?= $v ?></td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="unit">TOTAL</td>
                    <?php for ($b = 1; $b <= 12; $b++): ?>
                        <td><?= (int) ($totalPerBulan[$b] ?? 0) ?></td>
                    <?php endfor; ?>
                </tr>
            </tfoot>
        </table>

        <div class="legend">
            Keterangan intensitas:
            <span class="box" style="background:#ffffff;"></span> Normal (1&ndash;5)
            <span class="box" style="background:#fde68a;"></span> Sedang (6&ndash;10)
            <span class="box" style="background:#fca5a5;"></span> Tinggi (&gt;10)
        </div>
    </div>

    <script>
        window.addEventListener('load', function () { window.print(); });
    </script>
</body>
</html>
