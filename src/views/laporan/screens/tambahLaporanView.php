<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Laporan Baru - SIM-Perbaikan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/tambahLaporan.css">
</head>
<body>

    <div class="form-card">
        <!-- Form Header -->
        <div class="form-header">
            <div class="header-icon">
                <i class="ri-file-add-line"></i>
            </div>
            <div class="header-text">
                <h2>Tambah Laporan Baru</h2>
                <p>Input catatan kerusakan perangkat fasilitas rumah sakit</p>
            </div>
        </div>

        <!-- Form Input Container -->
        <form action="/laporan/simpan" method="POST" class="form-body">
            
            <!-- Grid 2 Kolom: Tanggal & Unit/Ruangan -->
            <div class="form-row">
                <div class="form-group">
                    <label for="tanggal">Tanggal</label>
                    <div class="input-icon-wrapper">
                        <i class="ri-calendar-event-line field-icon"></i>
                        <input type="date" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="unit">Unit/Ruangan</label>
                    <div class="input-icon-wrapper">
                        <i class="ri-door-open-line field-icon"></i>
                        <select id="unit" name="unit_id" required>
                            <option value="" disabled selected>Pilih Unit / Ruang</option>
                            <option value="1">POLI</option>
                            <option value="2">UGD</option>
                            <option value="3">MJKN</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Grid 2 Kolom: Jenis Barang & No Seri -->
            <div class="form-row">
                <div class="form-group">
                    <label for="jenis_barang">Jenis Barang</label>
                    <div class="input-icon-wrapper">
                        <i class="ri-inbox-archive-line field-icon"></i>
                        <select id="jenis_barang" name="barang_id" required>
                            <option value="" disabled selected>Pilih Jenis Barang</option>
                            <option value="1">Komputer</option>
                            <option value="2">Printer</option>
                            <option value="3">AC</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="no_seri">No Seri</label>
                    <div class="input-icon-wrapper">
                        <i class="ri-hashtag field-icon"></i>
                        <input type="text" id="no_seri" name="no_seri" placeholder="Contoh: SN-1234">
                    </div>
                </div>
            </div>

            <!-- Rincian Kerusakan -->
            <div class="form-group">
                <label for="rincian_kerusakan">Rincian Kerusakan</label>
                <textarea id="rincian_kerusakan" name="rincian_kerusakan" rows="3" placeholder="Contoh: SIMRS admisi tidak bisa" required></textarea>
            </div>

            <!-- Uraian Kegiatan -->
            <div class="form-group">
                <label for="uraian_kegiatan">Uraian Kegiatan</label>
                <textarea id="uraian_kegiatan" name="uraian_kegiatan" rows="3" placeholder="Contoh: Melakukan konfigurasi jaringan IP static"></textarea>
            </div>

            <!-- Status Penanganan -->
            <div class="form-group">
                <label for="status">Status Penanganan</label>
                <div class="input-icon-wrapper">
                    <i class="ri-tools-line field-icon"></i>
                    <select id="status" name="status" required>
                        <option value="" disabled selected>Pilih Status</option>
                        <option value="Proses">Proses</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
            </div>

            <!-- Form Footer Action Buttons -->
            <div class="form-actions">
                <a href="/laporan" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ri-save-line"></i> Simpan
                </button>
            </div>

        </form>
    </div>

</body>
</html>