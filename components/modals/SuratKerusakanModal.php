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
            <div id="suratReprintBadge" class="surat-reprint-badge" style="display:none;">
                <span class="material-symbols-outlined">replay</span>
                Cetak Ulang
            </div>
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

            <!-- Pratinjau TTD Digital -->
            <div class="surat-ttd-section">
                <div class="surat-ttd-label">
                    <span class="material-symbols-outlined">qr_code_2</span>
                    <strong>Tanda Tangan Digital</strong>
                    <span class="surat-ttd-hint">— Kode unik otomatis berdasarkan data laporan</span>
                </div>
                <div class="surat-ttd-preview">
                    <canvas id="suratBarcodeCanvas" width="200" height="60" title="Barcode TTD Digital"></canvas>
                    <div class="surat-ttd-meta">
                        <span class="surat-ttd-code" id="suratTtdCode">—</span>
                        <span class="surat-ttd-sub">RS Al-Huda · SIM-Perbaikan</span>
                    </div>
                </div>
            </div>

            <!-- Checklist Persetujuan -->
            <div class="surat-approval-box" id="suratApprovalBox">
                <label class="surat-approval-label" for="suratApprovalCheck">
                    <input type="checkbox" id="suratApprovalCheck" onchange="onSuratApprovalChange()">
                    <div class="surat-approval-text">
                        <strong>Saya menyetujui penggunaan tanda tangan digital</strong>
                        <span>Dengan mencentang ini, saya menyatakan data pada surat ini benar dan tanda tangan digital di atas berlaku sebagai persetujuan resmi.</span>
                        <span class="surat-reprint-note" id="suratReprintNote" style="display:none;">ℹ️ Mode cetak ulang — centang untuk mengaktifkan tombol cetak, atau langsung cetak jika sudah tercentang.</span>
                    </div>
                </label>
            </div>

            <!-- Tombol aksi -->
            <div class="surat-modal-actions">
                <button type="button" class="surat-btn surat-btn--outline" onclick="closeSuratModal()">
                    <span class="material-symbols-outlined">close</span> Batal
                </button>
                <button type="button" class="surat-btn surat-btn--print" id="suratBtnCetak" disabled onclick="cetakSurat()">
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
