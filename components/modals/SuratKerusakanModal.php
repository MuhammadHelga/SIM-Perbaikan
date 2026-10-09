<?php
/**
 * components/modals/SuratKerusakanModal.php
 * Modal surat pengantar kerusakan barang.
 * Dibuka oleh openSuratModal(id) di laporan.js.
 * Fitur:
 *   - Form isian surat (nama pelapor, jabatan, keterangan tambahan)
 *   - TTD digital berupa QR/barcode (dibuat di client via JS)
 *   - Checklist persetujuan TTD digital
 *   - Tombol Cetak muncul setelah centang
 */
?>
<!-- ===== OVERLAY SURAT KERUSAKAN ===== -->
<div class="modal-overlay" id="modal-surat-kerusakan">
    <div class="surat-modal-card">

        <!-- Header -->
        <div class="surat-modal-header">
            <div class="surat-modal-header__icon">
                <span class="material-symbols-outlined" id="suratModalHeaderIcon">description</span>
            </div>
            <div class="surat-modal-header__text">
                <h2 id="suratModalHeaderTitle">Form Surat Kerusakan</h2>
                <p id="suratModalHeaderDesc">Isi form di bawah sebelum mencetak surat pengantar kerusakan</p>
            </div>
            <!-- Badge reprint (hidden by default) -->
            <div id="suratReprintBadge" class="surat-reprint-badge" style="display:none;"></div>
            <button type="button" class="modal-close" onclick="closeSuratModal()" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Body: Form Isian -->
        <div class="surat-modal-body" id="suratFormSection">
            <div class="surat-form-grid">

                <!-- Info laporan (readonly, diisi JS) -->
                <div class="surat-info-box">
                    <span class="material-symbols-outlined">info</span>
                    <div>
                        <strong>Laporan #<span id="suratLaporanId">—</span></strong>
                        <span id="suratInfoDetail"></span>
                    </div>
                </div>

                <div class="surat-row-2">
                    <div class="surat-group">
                        <label for="suratNamaPelapor">Nama Pelapor <span class="req">*</span></label>
                        <div class="surat-input-wrap">
                            <span class="material-symbols-outlined">person</span>
                            <input type="text" id="suratNamaPelapor" placeholder="Nama lengkap pelapor" autocomplete="off">
                        </div>
                    </div>
                    <div class="surat-group">
                        <label for="suratJabatan">Jabatan / Bagian <span class="req">*</span></label>
                        <div class="surat-input-wrap">
                            <span class="material-symbols-outlined">badge</span>
                            <input type="text" id="suratJabatan" placeholder="cth: Staf IT / Kepala Ruangan" autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="surat-row-2">
                    <div class="surat-group">
                        <label for="suratTglSurat">Tanggal Surat <span class="req">*</span></label>
                        <div class="surat-input-wrap">
                            <span class="material-symbols-outlined">calendar_today</span>
                            <input type="date" id="suratTglSurat">
                        </div>
                    </div>
                    <div class="surat-group">
                        <label for="suratNomor">Nomor Surat</label>
                        <div class="surat-input-wrap">
                            <span class="material-symbols-outlined">tag</span>
                            <input type="text" id="suratNomor" placeholder="cth: 001/IT/X/2026">
                        </div>
                    </div>
                </div>

                <div class="surat-group">
                    <label for="suratKeterangan">Keterangan Tambahan</label>
                    <textarea id="suratKeterangan" rows="3" placeholder="Catatan tambahan untuk surat (opsional)"></textarea>
                </div>
            </div>

            <!-- Hidden Canvas for QR Generator -->
            <canvas id="suratQrCanvas" width="360" height="360" style="display:none;"></canvas>
            <span id="suratTtdCode" style="display:none;">—</span>

            <!-- Checklist Persetujuan -->
            <div class="surat-approval-box" id="suratApprovalBox">
                <label class="surat-approval-label" for="suratApprovalCheck">
                    <input type="checkbox" id="suratApprovalCheck" onchange="onSuratApprovalChange()">
                    <div class="surat-approval-text">
                        <strong>Saya menyetujui penggunaan tanda tangan digital (TTE)</strong>
                        <span>Dengan mencentang ini, saya menyatakan data pada surat ini benar.</span>
                    </div>
                </label>
            </div>

            <!-- Tombol aksi -->
            <div class="surat-modal-actions">
                <button type="button" class="surat-btn surat-btn--outline" onclick="closeSuratModal()">
                    <span class="material-symbols-outlined" id="suratBtnBatalIcon">close</span> <span id="suratBtnBatalLabel">Batal</span>
                </button>
                <button type="button" class="surat-btn surat-btn--print" id="suratBtnCetak" disabled onclick="cetakSurat()">
                    <span class="material-symbols-outlined">print</span> Cetak Surat
                </button>
            </div>
        </div>

        <!-- Body 2: Tampilan Surat Jadi (Dokumen Resmi Siap Cetak) -->
        <div class="surat-modal-body" id="suratDocumentPreviewSection" style="display: none;">
            <div class="surat-paper-wrapper">
                <div class="surat-paper-container">
                    <!-- Kop Surat -->
                    <div class="surat-paper-kop">
                        <img src="<?= BASE_URL ?>/assets/images/logo_alhuda_kop.png" class="surat-paper-logo" alt="Logo RS Al-Huda">
                        <div class="surat-paper-kop-text">
                            <h2>RUMAH SAKIT AL-HUDA</h2>
                            <p>Sistem Informasi Manajemen Perbaikan &amp; Kerusakan Perangkat (SIM-Perbaikan)</p>
                            <p>Jl. Raya Gambiran No. 225, Gambiran, Kab. Banyuwangi, Jawa Timur 68486</p>
                            <p>Telp: (0333) 842034 / 842038 | Email: rs_alhuda@yahoo.com | Web: www.rsalhuda.co.id</p>
                        </div>
                    </div>

                    <!-- Judul & Nomor -->
                    <div class="surat-paper-title">
                        <h3>SURAT PENGANTAR KERUSAKAN BARANG</h3>
                        <div class="surat-paper-nomor">Nomor: <span id="pvSuratNomor">—</span></div>
                    </div>

                    <div class="surat-paper-tgl">Tanggal: <span id="pvSuratTgl">—</span></div>

                    <div class="surat-paper-pembuka">
                        Yang bertanda tangan di bawah ini menyatakan bahwa barang/perangkat berikut telah mengalami kerusakan
                        dan perlu ditangani/dikirim ke unit terkait untuk perbaikan lebih lanjut.
                    </div>

                    <!-- Tabel Detail -->
                    <table class="surat-paper-table">
                        <tr><td style="width:36%; font-weight:600;">ID Laporan</td><td style="width:4%;">:</td><td>#<span id="pvSuratId">—</span></td></tr>
                        <tr><td style="font-weight:600;">Jenis Barang</td><td>:</td><td><span id="pvSuratBarang">—</span></td></tr>
                        <tr><td style="font-weight:600;">Unit / Ruangan</td><td>:</td><td><span id="pvSuratRuangan">—</span></td></tr>
                        <tr><td style="font-weight:600;">No. Seri (SN)</td><td>:</td><td><span id="pvSuratSn">—</span></td></tr>
                        <tr><td style="font-weight:600;">Tanggal Laporan</td><td>:</td><td><span id="pvSuratTglLaporan">—</span></td></tr>
                        <tr><td style="font-weight:600;">Status Penanganan</td><td>:</td><td><span id="pvSuratStatus">—</span></td></tr>
                        <tr><td style="font-weight:600;">Rincian Kerusakan</td><td>:</td><td><span id="pvSuratRincian">—</span></td></tr>
                        <tr><td style="font-weight:600;">Uraian Kegiatan</td><td>:</td><td><span id="pvSuratUraian">—</span></td></tr>
                    </table>

                    <div style="font-weight:600; margin: 12px 0 4px; font-size:11pt;">Keterangan Tambahan:</div>
                    <div class="surat-paper-keterangan" id="pvSuratKeterangan">(tidak ada keterangan tambahan)</div>

                    <!-- TTD & QR Code Row -->
                    <div class="surat-paper-ttd-row">
                        <div class="surat-paper-ttd-box">
                            <div class="ttd-lbl">Pelapor / TTE</div>
                            <div class="qr-preview-img-box">
                                <img id="pvSuratQrImg" src="" alt="QR Code TTE Berlogo" />
                            </div>
                            <div class="ttd-code-str" id="pvSuratTtdCode">—</div>
                            <div class="ttd-name-str"><strong id="pvSuratNamaPelapor">—</strong><br><small id="pvSuratJabatan">—</small></div>
                        </div>
                        <div class="surat-paper-ttd-box">
                            <div class="ttd-lbl">Mengetahui,</div>
                            <div class="stempel-box">Stempel &amp; Paraf</div>
                            <div class="ttd-name-str">___________________<br><small>Kepala Unit / Pejabat</small></div>
                        </div>
                    </div>

                    <!-- Footer Note -->
                    <div class="surat-paper-footer">
                        ★ Dokumen ini diterbitkan secara digital oleh SIM-Perbaikan RS Al-Huda. Scan QR Code untuk verifikasi keabsahan.
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="surat-modal-actions">
                <button type="button" class="surat-btn surat-btn--outline" onclick="closeSuratModal()">
                    <span class="material-symbols-outlined">close</span> Tutup
                </button>
                <button type="button" class="surat-btn surat-btn--print" onclick="cetakSurat()">
                    <span class="material-symbols-outlined">print</span> Cetak Surat
                </button>
            </div>
        </div>

    </div>
</div>

<!-- ===== FRAME CETAK SURAT (hidden, khusus @media print) ===== -->
<div id="suratCetakFrame" class="surat-cetak-frame" aria-hidden="true">
    <!-- Diisi JS saat cetak -->
</div>
