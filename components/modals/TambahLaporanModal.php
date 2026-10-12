<?php
/**
 * components/modals/TambahLaporanModal.php
 * Partial — dipakai untuk 3 mode: tambah, edit, dan lihat (read-only).
 * Mode diatur lewat JS: openTambahModal(), openEditModal(id), openDetailModal(id).
 *
 * CATATAN: judul, deskripsi, dan ikon header (formLaporanTitle/Desc/Icon) diatur
 * oleh setFormMode() di public/assets/js/laporan.js lewat konstanta FORM_MODES.
 * Teks di bawah hanya nilai awal sebelum modal dibuka — ubah teksnya di sana.
 */
?>
<div class="modal-overlay" id="modal-form-laporan">
    <div class="form-card">
        <div class="form-header">
            <div class="header-icon">
                <span class="material-symbols-outlined" id="formLaporanIcon"><!-- diisi JS --></span>
            </div>
            <div class="header-text">
                <h2 id="formLaporanTitle"><!-- diisi JS --></h2>
                <p id="formLaporanDesc"><!-- diisi JS --></p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('modal-form-laporan')" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="<?= BASE_URL ?>/laporan/simpan" method="POST" class="form-body" id="form-tambah-laporan">
            <?= csrfField() ?>
            <input type="hidden" id="form-laporan-id" name="id" value="">

            <h3 class="form-section-title">Waktu Laporan</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="tanggal">Tanggal laporan</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">calendar_month</span>
                        <input type="date" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="waktu_kejadian">Waktu kejadian <span class="form-label-optional">(opsional)</span></label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">schedule</span>
                        <input type="datetime-local" id="waktu_kejadian" name="waktu_kejadian" value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <small class="form-hint">Dapat disesuaikan jika laporan dicatat terlambat. Kosongkan jika waktu tidak diketahui.</small>
                </div>
            </div>

            <div class="form-row" id="laporanRecordedAt" hidden>
                <div class="form-group">
                    <label>Waktu dicatat di sistem</label>
                    <p class="form-readonly-value" id="laporanRecordedAtValue">-</p>
                </div>
                <div class="form-group"></div>
            </div>

            <h3 class="form-section-title">Lokasi &amp; Perangkat</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="unit">Unit/Ruangan</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">meeting_room</span>
                        <select id="unit" name="unit_id" required>
                            <option value="" disabled selected>Pilih Unit / Ruang</option>
                            <?php foreach (($ruanganList ?? []) as $ruangan): ?>
                                <option value="<?= (int)$ruangan['id'] ?>"><?= htmlspecialchars($ruangan['nama_ruangan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="jenis_barang">Jenis Barang</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">inventory_2</span>
                        <select id="jenis_barang" name="barang_id" required>
                            <option value="" disabled selected>Pilih Jenis Barang</option>
                            <?php foreach (($barangList ?? []) as $barang): ?>
                                <option value="<?= (int)$barang['id'] ?>"><?= htmlspecialchars($barang['nama_barang']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <small class="form-hint" id="barang-filter-hint" style="display:none;"></small>
                </div>
            </div>

            <div class="form-group" id="jenis-poli-group" style="display:none;" aria-hidden="true">
                <label for="jenis_poli">Jenis Poli</label>
                <div class="input-icon-wrapper">
                    <span class="material-symbols-outlined field-icon">medical_services</span>
                    <select id="jenis_poli" name="jenis_poli" disabled>
                        <option value="" disabled selected>Pilih Jenis Poli</option>
                        <?php foreach (poliJenisOptions() as $jenisPoli): ?>
                            <option value="<?= htmlspecialchars($jenisPoli, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($jenisPoli, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h3 class="form-section-title">Identitas Perangkat</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="no_seri">No Seri</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">tag</span>
                        <select id="no_seri" name="no_seri">
                            <option value="" disabled selected>Pilih No Seri</option>
                            <option value="" class="no-seri-manual-option">&#10133; Ketik manual&hellip;</option>
                        </select>
                        <input type="text" id="no_seri_manual" name="no_seri_manual" placeholder="Contoh: SN-1234" maxlength="50" autocomplete="off" style="display:none;">
                        <input type="hidden" id="aset_id" name="aset_id" value="">
                    </div>
                    <small class="form-hint">Serial bisa dipilih dari aset ruangan atau manual.</small>
                </div>

                <div class="form-group">
                    <label for="tipe_barang">Tipe / Model</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">build</span>
                        <input type="text" id="tipe_barang" name="tipe_barang" placeholder="Otomatis dari aset / ketik manual" maxlength="80" autocomplete="off">
                    </div>
                </div>
            </div>

            <h3 class="form-section-title">Detail Pekerjaan</h3>
            <div class="form-group">
                <label for="rincian_kerusakan">Rincian Kerusakan</label>
                <textarea id="rincian_kerusakan" name="rincian_kerusakan" rows="3" placeholder="Contoh: SIMRS admisi tidak bisa" required></textarea>
            </div>

            <div class="form-group">
                <label for="uraian_kegiatan">Uraian Kegiatan</label>
                <textarea id="uraian_kegiatan" name="uraian_kegiatan" rows="3" placeholder="Contoh: Melakukan konfigurasi jaringan IP static"></textarea>
            </div>

            <h3 class="form-section-title">Status &amp; Prioritas</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status Penanganan</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">build</span>
                        <select id="status" name="status" required>
                            <option value="" disabled selected>Pilih Status</option>
                            <option value="Proses">Proses</option>
                            <option value="Pending">Pending</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="prioritas">Prioritas</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">priority_high</span>
                        <select id="prioritas" name="prioritas" required>
                            <option value="" disabled selected>Pilih Prioritas</option>
                            <option value="Rendah">Rendah</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Tinggi">Tinggi</option>
                        </select>
                    </div>
                </div>
            </div>

            <section class="laporan-history" id="laporanHistory" hidden aria-labelledby="laporanHistoryTitle">
                <h3 id="laporanHistoryTitle">Riwayat Perubahan</h3>
                <ol class="laporan-history__list" id="laporanHistoryList" aria-live="polite"></ol>
            </section>

            <div class="form-actions" id="formLaporanActions">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-form-laporan')">Batal</button>
                <button type="submit" class="btn-simpan" id="formLaporanSubmitBtn">
                    <span class="material-symbols-outlined">save</span> Simpan
                </button>
            </div>

            <div class="form-actions" id="formLaporanViewActions" style="display:none;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-form-laporan')">Tutup</button>
            </div>
        </form>
    </div>
</div>
