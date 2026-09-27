document.addEventListener('DOMContentLoaded', function () {
    ['filterUnit', 'filterStatus'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => document.getElementById('jaringanFilterForm').requestSubmit());
    });

    const octetInput = document.getElementById('alokasiOctet');
    if (octetInput) {
        octetInput.addEventListener('input', updateAlokasiPreview);
    }

    const btnExport = document.getElementById('btnExportCsv');
    if (btnExport) btnExport.addEventListener('click', exportCsv);

    const btnPing = document.getElementById('btnPingSemua');
    if (btnPing) {
        btnPing.addEventListener('click', function () {
            openConfirmModal({
                icon: 'network_ping',
                title: 'Ping Semua Host',
                text: 'Jalankan ping ke seluruh host IP yang terdaftar? Proses ini mungkin memakan waktu beberapa saat.',
                confirmLabel: 'Ya, Jalankan',
                onConfirm: function () { alert('Menjalankan ping ke semua host... (fitur backend belum terhubung)'); }
            });
        });
    }
});

if (typeof openModal === 'undefined') {
    window.openModal = function (id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('open');
    };
}

if (typeof closeModal === 'undefined') {
    window.closeModal = function (id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('open');
    };
}

function getKomputerData() {
    const el = document.getElementById('komputerDataJson');
    if (!el) return [];
    try { return JSON.parse(el.textContent); } catch (e) { return []; }
}

function findKomputerById(id) {
    return getKomputerData().find(r => Number(r.id) === Number(id));
}

function openAlokasiModal() {
    const form = document.getElementById('form-alokasi-ip');
    if (form) form.reset();

    const idEl = document.getElementById('alokasiId');
    if (idEl) idEl.value = '';

    const titleEl = document.getElementById('alokasiTitle');
    if (titleEl) titleEl.textContent = 'Alokasi Host IP Komputer Unit';

    updateAlokasiPreview();
    openModal('modal-alokasi-ip');
}

function openEditAlokasiModal(id) {
    const row = findKomputerById(id);
    if (!row) { alert('Data tidak ditemukan.'); return; }

    const idEl = document.getElementById('alokasiId');
    if (idEl) idEl.value = row.id;

    const unitEl = document.getElementById('alokasiUnit');
    if (unitEl) unitEl.value = row.unit_id || '';

    const octetEl = document.getElementById('alokasiOctet');
    if (octetEl) octetEl.value = row.host_octet;

    const interfaceEl = document.getElementById('alokasiInterface');
    if (interfaceEl) interfaceEl.value = row.interface || 'LAN Port RJ-45 (Gigabit)';

    const macEl = document.getElementById('alokasiMac');
    if (macEl) macEl.value = row.mac || '';

    const portEl = document.getElementById('alokasiPort');
    if (portEl) portEl.value = row.port_switch || '';

    const catatanEl = document.getElementById('alokasiCatatan');
    if (catatanEl) catatanEl.value = row.catatan || '';

    const titleEl = document.getElementById('alokasiTitle');
    if (titleEl) titleEl.textContent = 'Edit Alokasi Host IP Komputer Unit';

    updateAlokasiPreview();
    openModal('modal-alokasi-ip');
}

function updateAlokasiPreview() {
    const prefix = window.JR_PREFIX || '192.100.99';
    const octetInput = document.getElementById('alokasiOctet');
    if (!octetInput) return;
    const val = parseInt(octetInput.value, 10);

    const statusEl = document.getElementById('alokasiOctetStatus');
    const fullIpEl = document.getElementById('alokasiFullIp');
    const prefixLabelEl = document.getElementById('alokasiPrefixLabel');
    const prefixInlineEl = document.getElementById('alokasiPrefixInline');

    if (prefixLabelEl) prefixLabelEl.textContent = prefix;
    if (prefixInlineEl) prefixInlineEl.textContent = prefix;

    if (!val || isNaN(val)) {
        if (fullIpEl) fullIpEl.textContent = prefix + '.—';
        if (statusEl) {
            statusEl.innerHTML = 'Status: <span>—</span>';
            statusEl.className = 'alokasi-status';
        }
        return;
    }

    if (fullIpEl) fullIpEl.textContent = prefix + '.' + val;

    const currentIdEl = document.getElementById('alokasiId');
    const currentId = currentIdEl ? currentIdEl.value : '';
    const used = getKomputerData().find(r => Number(r.host_octet) === val && String(r.id) !== String(currentId));

    if (statusEl) {
        if (val >= 1 && val <= 9) {
            statusEl.innerHTML = 'Status: <span>Reserved (Core/Gateway)</span>';
            statusEl.className = 'alokasi-status is-core';
        } else if (used) {
            statusEl.innerHTML = 'Status: <span>Sudah Dipakai (' + used.hostname + ')</span>';
            statusEl.className = 'alokasi-status is-taken';
        } else if (val < 10 || val > 254) {
            statusEl.innerHTML = 'Status: <span>Di luar range valid</span>';
            statusEl.className = 'alokasi-status is-taken';
        } else {
            statusEl.innerHTML = 'Status: <span>Kosong &amp; Tersedia</span>';
            statusEl.className = 'alokasi-status is-available';
        }
    }
}

function pingHost(id, ip) {
    openConfirmModal({
        icon: 'network_ping',
        title: 'Ping Host',
        text: 'Jalankan ping ke ' + ip + '?',
        confirmLabel: 'Ya, Ping',
        onConfirm: function () { alert('Ping ke ' + ip + '... (fitur backend belum terhubung)'); }
    });
}

function exportCsv() {
    const rows = getKomputerData();
    if (!rows.length) { alert('Tidak ada data untuk diekspor.'); return; }

    const prefix = window.JR_PREFIX || '192.100.99';
    const header = ['Nama Unit', 'Lokasi', 'Hostname', 'Alamat IP', 'Status'];
    const lines = [header.join(',')];

    rows.forEach(function (r) {
        const line = [
            '"' + (r.unit || '') + '"',
            '"' + (r.lokasi || '') + '"',
            r.hostname || '',
            prefix + '.' + r.host_octet,
            r.status || '',
        ];
        lines.push(line.join(','));
    });

    const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'alokasi-ip-jaringan.csv';
    a.click();
    URL.revokeObjectURL(url);
}