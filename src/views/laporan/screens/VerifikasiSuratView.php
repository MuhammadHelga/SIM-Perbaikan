<?php
/**
 * View Verifikasi Surat TTE / Digital QR Code
 * Menampilkan status keabsahan naskah dinas perbaikan RS Al-Huda
 */

$id = (int) ($row['id'] ?? 0);
$barang = $row['barang'] ?? 'Perangkat';
$urusan = $row['urusan'] ?? 'Unit';
$tgl = $row['tanggal'] ?? date('Y-m-d');
$statusPenanganan = $row['status_penanganan'] ?? 'Pending';
$kirimStatus = $row['kirim_status'] ?? 'belum';
$tglKirim = $row['tgl_kirim'] ?? $tgl;

// Tanggal surat: pakai yang tersimpan bila ada, jika tidak pakai tgl kirim/laporan.
$tglSuratRaw = !empty($row['tgl_surat']) ? $row['tgl_surat'] : $tglKirim;

// Format tanggal Indonesia
$bulanIndo = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$d = strtotime($tglSuratRaw);
$tglSuratFmt = date('d', $d) . ' ' . $bulanIndo[(int)date('m', $d)] . ' ' . date('Y', $d);

$blnRomawi = ['','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
$blnNum = (int)date('m', $d);
$thnNum = date('Y', $d);
$nomorSurat = !empty($row['nomor_surat'])
    ? $row['nomor_surat']
    : sprintf('%03d/IT/%s/%s', $id, $blnRomawi[$blnNum], $thnNum);

// Penandatangan diambil dari data yang tersimpan (diisi saat surat dicetak),
// bukan dari URL. Halaman ini hanya dirender saat verify_token cocok
// (lihat src/routes/laporan.php), jadi badge "DOKUMEN VALID" benar-benar sah.
$namaPelapor = !empty($row['nama_pelapor']) ? $row['nama_pelapor'] : 'Petugas Unit IT';
$jabatanPelapor = !empty($row['jabatan_pelapor']) ? $row['jabatan_pelapor'] : 'Penanggung Jawab / Staf IT';

// Kode TTE diturunkan dari verify_token tersimpan, bukan dari query.
$verifyToken = (string) ($row['verify_token'] ?? '');
$ttdCode = sprintf('LPR%04d-TTE-%s', $id, strtoupper(substr($verifyToken, 0, 6)));

$statusSurat = 'Published';
if ($kirimStatus === 'dikirim') $statusSurat = 'Dikirim (Proses)';
if ($kirimStatus === 'diterima') $statusSurat = 'Diterima Unit Perbaikan';

$base = BASE_URL;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Surat TTE #<?= $id ?> - RS Al-Huda</title>
    <link rel="icon" href="<?= $base ?>/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00288e;
            --primary-light: #eff4ff;
            --success: #16a34a;
            --success-light: #f0fdf4;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: var(--text-dark);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 24px 16px;
        }

        .verify-card {
            background: #ffffff;
            width: 100%;
            max-width: 680px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        /* Top Banner Ribbon Tag "TTE" */
        .tte-ribbon {
            position: absolute;
            top: 24px;
            right: -35px;
            background: #16a34a;
            color: #ffffff;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 1px;
            padding: 6px 40px;
            transform: rotate(45deg);
            box-shadow: 0 2px 6px rgba(22, 163, 74, 0.3);
            z-index: 10;
        }

        /* Kop Header */
        .verify-header {
            padding: 28px 32px 20px;
            border-bottom: 2px dashed var(--border);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .verify-header img {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }

        .header-title h1 {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.2;
            letter-spacing: -0.3px;
        }

        .header-title p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 3px;
            font-weight: 500;
        }

        /* Card Content with Watermark */
        .verify-body {
            padding: 28px 32px;
            position: relative;
            background: #ffffff;
        }

        /* Faint Watermark Text */
        .watermark-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 42px;
            font-weight: 900;
            color: rgba(34, 197, 94, 0.07);
            white-space: nowrap;
            user-select: none;
            pointer-events: none;
            letter-spacing: 4px;
            z-index: 1;
            text-align: center;
            width: 100%;
        }

        .content-relative {
            position: relative;
            z-index: 2;
        }

        .signed-notice {
            font-size: 14.5px;
            color: #334155;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .signed-notice strong {
            color: var(--text-dark);
        }

        .signer-name {
            font-size: 17px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 3px;
        }

        .signer-role {
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 24px;
        }

        /* Metadata Table */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .meta-table td {
            padding: 9px 0;
            vertical-align: top;
            font-size: 14px;
        }

        .meta-table td.label-col {
            width: 170px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .meta-table td.colon-col {
            width: 20px;
            color: var(--text-muted);
        }

        .meta-table td.val-col {
            color: var(--text-dark);
            font-weight: 600;
        }

        .badge-disetujui {
            color: #dc2626;
            font-weight: 700;
            font-size: 13px;
            margin-left: 6px;
        }

        /* Extra Details Box */
        .extra-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-top: 10px;
        }

        .extra-box h3 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 12px;
            font-weight: 700;
        }

        .extra-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            font-size: 13.5px;
        }

        .extra-item label {
            display: block;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 2px;
        }

        .extra-item span {
            font-weight: 600;
            color: var(--text-dark);
        }

        .code-pill {
            font-family: monospace;
            background: #e0e7ff;
            color: #3730a3;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
        }

        /* Footer */
        .verify-footer {
            padding: 16px 32px;
            background: #f1f5f9;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
        }

        .status-badge-valid {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #dcfce7;
            color: #15803d;
            font-weight: 700;
            font-size: 12.5px;
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid #bbf7d0;
        }

        .status-badge-valid svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        @media (max-width: 540px) {
            .verify-header {
                padding: 20px 20px 16px;
            }
            .verify-body {
                padding: 20px;
            }
            .meta-table td.label-col {
                width: 120px;
            }
            .extra-grid {
                grid-template-columns: 1fr;
            }
            .verify-footer {
                padding: 14px 20px;
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<div class="verify-card">
    <!-- Green TTE Ribbon Tag -->
    <div class="tte-ribbon">TTE</div>

    <!-- Kop Header -->
    <div class="verify-header">
        <img src="<?= $base ?>/assets/images/logo_alhuda.svg" alt="Logo RS Al-Huda">
        <div class="header-title">
            <h1>RUMAH SAKIT AL-HUDA</h1>
            <p>Sistem Informasi Manajemen Perbaikan &amp; Kerusakan (SIM-Perbaikan)</p>
        </div>
    </div>

    <!-- Body Content -->
    <div class="verify-body">
        <!-- Faint Watermark Text -->
        <div class="watermark-bg">
            RS AL-HUDA VALIDATION<br>
            <?= htmlspecialchars($ttdCode) ?>
        </div>

        <div class="content-relative">
            <div class="signed-notice">
                Naskah dinas ini <strong>telah</strong> ditandatangani secara elektronik oleh:
            </div>

            <div class="signer-name"><?= htmlspecialchars($namaPelapor) ?></div>
            <div class="signer-role"><?= htmlspecialchars($jabatanPelapor) ?></div>

            <table class="meta-table">
                <tr>
                    <td class="label-col">Tanggal Surat</td>
                    <td class="colon-col">:</td>
                    <td class="val-col"><?= htmlspecialchars($tglSuratFmt) ?></td>
                </tr>
                <tr>
                    <td class="label-col">Nomor Surat</td>
                    <td class="colon-col">:</td>
                    <td class="val-col"><?= htmlspecialchars($nomorSurat) ?></td>
                </tr>
                <tr>
                    <td class="label-col">Jenis Surat</td>
                    <td class="colon-col">:</td>
                    <td class="val-col">Surat Pengantar Kerusakan Barang</td>
                </tr>
                <tr>
                    <td class="label-col">Jenis Tandatangan</td>
                    <td class="colon-col">:</td>
                    <td class="val-col">Internal RS Al-Huda (TTE Digital)</td>
                </tr>
                <tr>
                    <td class="label-col">Drafter</td>
                    <td class="colon-col">:</td>
                    <td class="val-col"><?= htmlspecialchars($namaPelapor) ?></td>
                </tr>
                <tr>
                    <td class="label-col">Pemaraf</td>
                    <td class="colon-col">:</td>
                    <td class="val-col">1. <?= htmlspecialchars($namaPelapor) ?> <span class="badge-disetujui">*Disetujui</span></td>
                </tr>
                <tr>
                    <td class="label-col">Status Surat</td>
                    <td class="colon-col">:</td>
                    <td class="val-col"><?= htmlspecialchars($statusSurat) ?></td>
                </tr>
            </table>

            <!-- Detail Perangkat -->
            <div class="extra-box">
                <h3>Detail Laporan Perangkat</h3>
                <div class="extra-grid">
                    <div class="extra-item">
                        <label>ID Laporan</label>
                        <span>#<?= $id ?></span>
                    </div>
                    <div class="extra-item">
                        <label>Nama Barang / Perangkat</label>
                        <span><?= htmlspecialchars($barang) ?></span>
                    </div>
                    <div class="extra-item">
                        <label>Unit / Ruangan</label>
                        <span><?= htmlspecialchars($urusan) ?></span>
                    </div>
                    <div class="extra-item">
                        <label>Serial Number (SN)</label>
                        <span><?= htmlspecialchars($row['serial_number'] ?? '-') ?></span>
                    </div>
                    <div class="extra-item" style="grid-column: 1 / -1;">
                        <label>Rincian Kerusakan</label>
                        <span><?= htmlspecialchars($row['rincian_kerusakan'] ?? 'Kerusakan Perangkat') ?></span>
                    </div>
                    <div class="extra-item" style="grid-column: 1 / -1; margin-top: 4px;">
                        <label>Kode TTE</label>
                        <span class="code-pill"><?= htmlspecialchars($ttdCode) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Status -->
    <div class="verify-footer">
        <div>Official Verification Page &bull; SIM-Perbaikan RS Al-Huda</div>
        <div class="status-badge-valid">
            <svg viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            DOKUMEN VALID
        </div>
    </div>
</div>

</body>
</html>
