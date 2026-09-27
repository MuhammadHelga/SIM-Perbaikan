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
                <label for="alokasiUnit">Pilih Komputer Unit / Ruangan <span class="req">*</span></label>
                <select name="unit_id" id="alokasiUnit" required>
                    <option value="" disabled selected>Pilih Unit / Ruangan</option>
                    <?php foreach (($unitList ?? []) as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['label']) ?></option>
                    <?php endforeach; ?>
                </select>
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

            <div class="alokasi-row">
                <div class="alokasi-field">
                    <label for="alokasiInterface">Tipe Koneksi &amp; Interface</label>
                    <select name="interface" id="alokasiInterface">
                        <option value="LAN Port RJ-45 (Gigabit)">LAN Port RJ-45 (Gigabit)</option>
                        <option value="LAN Port RJ-45 (Fast Ethernet)">LAN Port RJ-45 (Fast Ethernet)</option>
                        <option value="WiFi Access Point">WiFi Access Point</option>
                        <option value="Fiber Optic">Fiber Optic</option>
                    </select>
                </div>
                <div class="alokasi-field">
                    <label for="alokasiMac">MAC Address Workstation</label>
                    <input type="text" name="mac_address" id="alokasiMac" placeholder="D4:5D:64:A2:18:95">
                </div>
            </div>

            <div class="alokasi-field">
                <label for="alokasiPort">Keterangan Port Switch / Patch Panel</label>
                <input type="text" name="port_switch" id="alokasiPort" placeholder="Switch Poli Lt.2 - Port G0/19">
            </div>

            <div class="alokasi-field">
                <label for="alokasiCatatan">Catatan / Posisi Meja Unit</label>
                <input type="text" name="catatan" id="alokasiCatatan" placeholder="Meja Pendaftaran Poli Anak 01">
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