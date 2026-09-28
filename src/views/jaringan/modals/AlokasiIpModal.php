<?php
/**
 * Modal Alokasi & Edit Host IP.
 * Mode ditentukan lewat JS: openAlokasiModal() (tambah) / openEditAlokasiModal(id) (edit).
 */
?>
<div class="modal-overlay" id="modal-alokasi-ip">
    <div class="alokasi-card">
        <div class="alokasi-card__head">
            <div>
                <h2 id="alokasiTitle">Alokasi &amp; Edit Host IP Komputer Unit</h2>
                <p>Form konfigurasi pemetaan alamat IP workstation rumah sakit</p>
            </div>
            <span class="jr-chip jr-chip--head">
                <span id="alokasiPrefixLabel">192.100.99</span>.xx <span class="jr-chip__scope">/24</span>
            </span>
            <button type="button" class="modal-close" onclick="closeModal('modal-alokasi-ip')" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="<?= BASE_URL ?>/jaringan/simpan" method="POST" class="alokasi-card__body" id="form-alokasi-ip">
            <input type="hidden" name="id" id="alokasiId" value="">

            <div class="alokasi-field">
                <label for="alokasiUnit">Nama Unit / Ruangan <span class="req">*</span></label>
                <input type="text" name="unit" id="alokasiUnit" placeholder="Contoh: Loket Admisi 1 (Rawat Inap)" required>
            </div>

            <div class="alokasi-row">
                <div class="alokasi-field">
                    <label for="alokasiLokasi">Lokasi / Gedung</label>
                    <input type="text" name="lokasi" id="alokasiLokasi" placeholder="Contoh: Gedung A - Lantai 1">
                </div>
                <div class="alokasi-field">
                    <label for="alokasiHostname">Hostname Komputer <span class="req">*</span></label>
                    <input type="text" name="hostname" id="alokasiHostname" placeholder="Contoh: PC-ADMISI-01" required>
                </div>
            </div>

            <div class="alokasi-field">
                <label for="alokasiOctet">Input Host IP (Oktet Ke-4) <span class="req">*</span></label>
                <div class="alokasi-octet-input">
                    <span class="alokasi-octet-input__prefix">@ <span id="alokasiPrefixInline">192.100.99</span>.</span>
                    <input type="number" name="host_octet" id="alokasiOctet" min="10" max="254" placeholder="95" required>
                </div>
                <p class="alokasi-hint">Range valid unit: 10 s/d 254 (Host 1&ndash;9 khusus Core/Gateway)</p>
                <p class="alokasi-status" id="alokasiOctetStatus">Status: <span>&mdash;</span></p>
            </div>

            <div class="alokasi-result" id="alokasiResult">
                <div>
                    <p class="alokasi-result__label">Hasil Konfigurasi IP Penuh:</p>
                    <p class="alokasi-result__ip" id="alokasiFullIp">192.100.99.&mdash;</p>
                    <p class="alokasi-result__mask">Subnet Mask 255.255.255.0</p>
                </div>
                <span class="alokasi-result__badge" id="alokasiReadyBadge">
                    <span class="material-symbols-outlined">check_circle</span> Siap Diterapkan
                </span>
            </div>

            <div class="alokasi-actions">
                <button type="button" class="jr-btn jr-btn--outline" onclick="closeModal('modal-alokasi-ip')">Batal</button>
                <button type="submit" class="jr-btn jr-btn--primary">
                    <span class="material-symbols-outlined">save</span> Simpan Alokasi IP
                </button>
            </div>
        </form>
    </div>
</div>