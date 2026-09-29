<?php
/**
 * Cetak Dashboard: ringkasan KPI, tabel rekap bulanan, grafik, dan tabel distribusi.
 * Dibuka dari tombol "Cetak Dashboard" (tab baru) dan otomatis memicu print.
 *
 * @var array $dashboard
 * @var int[] $tahunList
 */
$dashboard = $dashboard ?? [
    'total' => 0, 'dalam' => 0, 'selesai' => 0, 'kritis' => 0,
    'solveRate' => 0, 'delta' => null, 'tahun' => (int) date('Y'), 'tahunLalu' => (int) date('Y') - 1,
    'monthly' => ['masuk' => array_fill(1, 12, 0), 'pending' => array_fill(1, 12, 0), 'proses' => array_fill(1, 12, 0), 'selesai' => array_fill(1, 12, 0)],
    'byBarang' => [],
    'monthlyByBarang' => [],
];

$bulanSingkat = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

$monthly     = $dashboard['monthly'];
$byBarang    = $dashboard['byBarang'];
$monthlyItem = $dashboard['monthlyByBarang'];

$totalByBarang = 0;
foreach ($byBarang as $b) {
    $totalByBarang += (int) ($b['jumlah'] ?? 0);
}

$deltaText = $dashboard['delta'] === null
    ? 'Belum ada pembanding'
    : ($dashboard['delta'] >= 0
        ? '+' . (int) $dashboard['delta'] . ' % vs tahun lalu'
        : (int) $dashboard['delta'] . ' % vs tahun lalu');

$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Perbaikan <?= $esc($dashboard['tahun']) ?></title>
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
        .head { text-align: center; border-bottom: 2px solid #00288e; padding-bottom: 12px; margin-bottom: 14px; }
        .head h1 { margin: 0; font-size: 18px; color: #00288e; }
        .head h2 { margin: 4px 0 0; font-size: 14px; font-weight: 600; }
        .meta { margin-top: 6px; font-size: 12px; color: #444; }
        .meta span { display: inline-block; margin: 0 8px; }

        /* Kartu ringkasan */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .kpi {
            border: 1px solid #cfd6e6;
            border-left: 3px solid #00288e;
            border-radius: 6px;
            padding: 8px 10px;
            background: #f7f9fd;
        }
        .kpi__label { font-size: 9.5px; font-weight: 700; letter-spacing: 0.4px; color: #64748b; text-transform: uppercase; }
        .kpi__value { font-size: 20px; font-weight: 800; line-height: 1.2; }
        .kpi__value small { font-size: 10px; font-weight: 600; color: #64748b; }
        .kpi__foot { font-size: 10px; color: #64748b; }
        .kpi--purple { border-left-color: #992ac9; }
        .kpi--purple .kpi__value { color: #992ac9; }
        .kpi--amber { border-left-color: #d97706; }
        .kpi--amber .kpi__value { color: #d97706; }
        .kpi--green { border-left-color: #16a34a; }
        .kpi--green .kpi__value { color: #16a34a; }
        .kpi--red { border-left-color: #dc2626; }
        .kpi--red .kpi__value { color: #dc2626; }

        .block { margin-bottom: 18px; break-inside: avoid; }
        .block__title {
            font-size: 12px;
            font-weight: 700;
            color: #00288e;
            margin: 0 0 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #dbe2f0;
        }
        .charts {
            display: grid;
            grid-template-columns: 1.8fr 1.2fr;
            gap: 14px;
        }
        .chart-box { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px; }
        .chart-box canvas { display: block; width: 100% !important; height: 240px !important; }

        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #b9c2d6; padding: 4px 6px; text-align: center; }
        th { background: #00288e; color: #fff; font-weight: 600; }
        th.row-label, td.row-label { text-align: left; font-weight: 600; }
        tbody tr:nth-child(even) { background: #f5f7fc; }
        tfoot td { font-weight: 700; background: #e0e4ff; }
        td.center, th.center { text-align: center; }
        .empty { text-align: center; padding: 18px; color: #666; }

        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; max-width: none; padding: 0; }
            .toolbar { display: none !important; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
            tr { break-inside: avoid; page-break-inside: avoid; }
            .block { break-inside: avoid; page-break-inside: avoid; }
            .page-break { break-before: page; page-break-before: always; }
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
            <h2>Dashboard dan Monitoring Perbaikan &mdash; Tahun <?= (int) $dashboard['tahun'] ?></h2>
            <div class="meta">
                <span><strong>Total Laporan:</strong> <?= (int) $dashboard['total'] ?></span>
                <span><strong>Solve Rate:</strong> <?= (int) $dashboard['solveRate'] ?>%</span>
                <span><strong>Perubahan:</strong> <?= $esc($deltaText) ?></span>
                <span><strong>Dicetak:</strong> <?= date('d M Y H:i') ?></span>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi kpi--purple">
                <div class="kpi__label">Total Laporan</div>
                <div class="kpi__value"><?= (int) $dashboard['total'] ?> <small>KASUS</small></div>
                <div class="kpi__foot"><?= $esc($deltaText) ?></div>
            </div>
            <div class="kpi kpi--amber">
                <div class="kpi__label">Dalam Penanganan</div>
                <div class="kpi__value"><?= (int) $dashboard['dalam'] ?> <small>UNIT</small></div>
                <div class="kpi__foot">Butuh tindakan cepat</div>
            </div>
            <div class="kpi kpi--green">
                <div class="kpi__label">Selesai Ditangani</div>
                <div class="kpi__value"><?= (int) $dashboard['selesai'] ?> <small>UNIT</small></div>
                <div class="kpi__foot"><?= (int) $dashboard['solveRate'] ?>% Solve Rate</div>
            </div>
            <div class="kpi kpi--red">
                <div class="kpi__label">Kerusakan Kritis</div>
                <div class="kpi__value"><?= (int) $dashboard['kritis'] ?> <small>UNIT</small></div>
                <div class="kpi__foot">Prioritas tinggi perbaikan</div>
            </div>
        </div>

        <div class="block">
            <h3 class="block__title">Tren Laporan dan Penyelesaian per Bulan</h3>
            <div class="charts">
                <div class="chart-box"><canvas id="barChart"></canvas></div>
                <div class="chart-box"><canvas id="doughnutChart"></canvas></div>
            </div>
        </div>

        <div class="block">
            <h3 class="block__title">Rekap Bulanan Tahun <?= (int) $dashboard['tahun'] ?></h3>
            <table>
                <thead>
                    <tr>
                        <th class="row-label" style="width: 22%;">Status</th>
                        <?php foreach ($bulanSingkat as $bln): ?>
                            <th><?= $esc($bln) ?></th>
                        <?php endforeach; ?>
                        <th>TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $baris = [
                        'masuk'   => 'Laporan Masuk',
                        'pending' => 'Pending',
                        'proses'  => 'Proses',
                        'selesai' => 'Selesai',
                    ];
                    $jumlahBulan = ['masuk' => 0, 'pending' => 0, 'proses' => 0, 'selesai' => 0];
                    ?>
                    <?php foreach ($baris as $key => $label): ?>
                        <tr>
                            <td class="row-label"><?= $esc($label) ?></td>
                            <?php for ($b = 1; $b <= 12; $b++): ?>
                                <?php $v = (int) ($monthly[$key][$b] ?? 0); $jumlahBulan[$key] += $v; ?>
                                <td><?= $v ?></td>
                            <?php endfor; ?>
                            <td><?= $jumlahBulan[$key] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="row-label">JUMLAH PER BULAN</td>
                        <?php for ($b = 1; $b <= 12; $b++): ?>
                            <td><?= (int) ($monthly['masuk'][$b] ?? 0) ?></td>
                        <?php endfor; ?>
                        <td><?= (int) $dashboard['total'] ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="block page-break">
            <h3 class="block__title">Distribusi Perangkat(termasuk Kerusakan Terbanyak)</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th class="row-label">Nama Barang</th>
                        <th style="width: 90px;">Jumlah</th>
                        <th style="width: 90px;">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$byBarang): ?>
                        <tr><td class="empty" colspan="4">Belum ada data untuk tahun ini.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($byBarang as $i => $b): ?>
                        <?php $jumlah = (int) ($b['jumlah'] ?? 0); ?>
                        <tr>
                            <td class="center"><?= $i + 1 ?></td>
                            <td class="row-label"><?= $esc($b['nama'] ?? '-') ?></td>
                            <td><?= $jumlah ?></td>
                            <td><?= $totalByBarang > 0 ? number_format($jumlah / $totalByBarang * 100, 1, ',', '.') : '0,0' ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if ($byBarang): ?>
                    <tfoot>
                        <tr>
                            <td class="center"><?= count($byBarang) ?></td>
                            <td class="row-label">TOTAL</td>
                            <td><?= $totalByBarang ?></td>
                            <td>100,0%</td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <div class="block">
            <h3 class="block__title">Kerusakan per Barang per Bulan</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th class="row-label">Nama Barang</th>
                        <?php foreach ($bulanSingkat as $bln): ?>
                            <th><?= $esc($bln) ?></th>
                        <?php endforeach; ?>
                        <th>TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$monthlyItem): ?>
                        <tr><td class="empty" colspan="15">Belum ada data untuk tahun ini.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($monthlyItem as $i => $item): ?>
                        <?php $rowTotal = 0; ?>
                        <tr>
                            <td class="center"><?= $i + 1 ?></td>
                            <td class="row-label"><?= $esc($item['nama'] ?? '-') ?></td>
                            <?php for ($b = 1; $b <= 12; $b++): ?>
                                <?php $v = (int) (($item['bulanan'] ?? [])[$b] ?? 0); $rowTotal += $v; ?>
                                <td><?= $v ?></td>
                            <?php endfor; ?>
                            <td><?= $rowTotal ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if ($monthlyItem): ?>
                    <tfoot>
                        <tr>
                            <td class="center"><?= count($monthlyItem) ?></td>
                            <td class="row-label">TOTAL</td>
                            <?php for ($b = 1; $b <= 12; $b++): ?>
                                <td><?= (int) ($monthly['masuk'][$b] ?? 0) ?></td>
                            <?php endfor; ?>
                            <td><?= (int) $dashboard['total'] ?></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <script id="dashboardCetakDataJson" type="application/json"><?= json_encode([
        'monthly'         => $monthly,
        'byBarang'        => $byBarang,
        'monthlyByBarang' => $monthlyItem,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) ?></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/dashboard-cetak.js?v=<?= filemtime(dirname(__DIR__, 4) . '/public/assets/js/dashboard-cetak.js') ?>"></script>
</body>
</html>
