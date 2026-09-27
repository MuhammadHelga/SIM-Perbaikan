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
    form.reset();
    document.getElementById('alokasiId').value = '';
    document.getElementById('alokasiTitle').textContent = 'Alokasi Host IP Komputer Unit';
    updateAlokasiPreview();
    openModal('modal-alokasi-ip');
}

function openEditAlokasiModal(id) {
    const row = findKomputerById(id);
    if (!row) { alert('Data tidak ditemukan.'); return; }

    document.getElementById('alokasiId').value = row.id;
    document.getElementById('alokasiUnit').value = row.unit_id || '';
    document.getElementById('alokasiOctet').value = row.host_octet;
    document.getElementById('alokasiInterface').value = row.interface || 'LAN Port RJ-45 (Gigabit)';
    document.getElementById('alokasiMac').value = row.mac || '';
    document.getElementById('alokasiPort').value = row.port_switch || '';
    document.getElementById('alokasiCatatan').value = row.catatan || '';
    document.getElementById('alokasiTitle').textContent = 'Edit Alokasi Host IP Komputer Unit';

    updateAlokasiPreview();
    openModal('modal-alokasi-ip');
}

function updateAlokasiPreview() {
    const prefix = window.JR_PREFIX || '192.100.99';
    const octetInput = document.getElementById('alokasiOctet');
    const val = parseInt(octetInput.value, 10);

    const statusEl = document.getElementById('alokasiOctetStatus');
    const fullIpEl = document.getElementById('alokasiFullIp');

    document.getElementById('alokasiPrefixLabel').textContent = prefix;
    document.getElementById('alokasiPrefixInline').textContent = prefix;

    if (!val || isNaN(val)) {
        fullIpEl.textContent = prefix + '.—';
        statusEl.innerHTML = 'Status: <span>—</span>';
        statusEl.className = 'alokasi-status';
        return;
    }

    fullIpEl.textContent = prefix + '.' + val;

    const currentId = document.getElementById('alokasiId').value;
    const used = getKomputerData().find(r => Number(r.host_octet) === val && String(r.id) !== String(currentId));

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