<?php
/**
 * components/modals/TambahLaporanModal.php
 * Partial — dipakai untuk 3 mode: tambah, edit, dan lihat (read-only).
 * Mode diatur lewat JS: openTambahModal(), openEditModal(id), openDetailModal(id).
 */
?>
<div class="modal-overlay" id="modal-form-laporan">
    <div class="form-card">
        <div class="form-header">
            <div class="header-icon">
                <span class="material-symbols-outlined" id="formLaporanIcon">note_add</span>
            </div>
            <div class="header-text">
                <h2 id="formLaporanTitle">Tambah Laporan Baru</h2>
                <p id="formLaporanDesc">Input catatan kerusakan perangkat fasilitas rumah sakit</p>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('modal-form-laporan')" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="<?= BASE_URL ?>/laporan/simpan" method="POST" class="form-body" id="form-tambah-laporan">
            <input type="hidden" id="form-laporan-id" name="id" value="">

            <div class="form-row">
                <div class="form-group">
                    <label for="tanggal">Tanggal</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">calendar_month</span>
                        <input type="date" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

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
            </div>

            <div class="form-row">
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
                </div>

                <div class="form-group">
                    <label for="no_seri">No Seri</label>
                    <div class="input-icon-wrapper">
                        <span class="material-symbols-outlined field-icon">tag</span>
                        <input type="text" id="no_seri" name="no_seri" placeholder="Contoh: SN-1234">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="rincian_kerusakan">Rincian Kerusakan</label>
                <textarea id="rincian_kerusakan" name="rincian_kerusakan" rows="3" placeholder="Contoh: SIMRS admisi tidak bisa" required></textarea>
            </div>

            <div class="form-group">
                <label for="uraian_kegiatan">Uraian Kegiatan</label>
                <textarea id="uraian_kegiatan" name="uraian_kegiatan" rows="3" placeholder="Contoh: Melakukan konfigurasi jaringan IP static"></textarea>
            </div>

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
