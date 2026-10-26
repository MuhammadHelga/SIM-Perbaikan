document.addEventListener('DOMContentLoaded', function () {
    ['filterUnit', 'filterStatus'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => document.getElementById('jaringanFilterForm').requestSubmit());
    });

    const octetInput = document.getElementById('alokasiOctet');
    if (octetInput) {
        octetInput.addEventListener('input', updateAlokasiPreview);
    }

    const subnetSelect = document.getElementById('alokasiSubnet');
    if (subnetSelect) {
        subnetSelect.addEventListener('change', updateAlokasiPreview);
    }

    const btnExport = document.getElementById('btnExportCsv');
    if (btnExport) btnExport.addEventListener('click', exportCsv);
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

function currentSubnetPrefix() {
    const sel = document.getElementById('alokasiSubnet');
    if (sel && sel.selectedOptions && sel.selectedOptions[0]) {
        const p = sel.selectedOptions[0].getAttribute('data-prefix');
        if (p) return p;
    }
    const map = window.JR_SUBNETS || {};
    const cidr = window.JR_DEFAULT_SUBNET || '';
    if (map[cidr]) return map[cidr];
    const any = Object.values(map)[0];
    return any || '192.100.99';
}

function openAlokasiModal() {
    const form = document.getElementById('form-alokasi-ip');
    if (form) form.reset();

    const idEl = document.getElementById('alokasiId');
    if (idEl) idEl.value = '';

    const sel = document.getElementById('alokasiSubnet');
    if (sel && window.JR_DEFAULT_SUBNET) sel.value = window.JR_DEFAULT_SUBNET;

    const titleEl = document.getElementById('alokasiTitle');
    if (titleEl) titleEl.textContent = 'Alokasi Host IP Komputer Unit';

    updateAlokasiPreview();
    openModal('modal-alokasi-ip');
}

function openEditAlokasiModal(id) {
    const row = findKomputerById(id);
    if (!row) { showToast('Data tidak ditemukan.', 'error'); return; }

    document.getElementById('alokasiId').value = row.id;
    document.getElementById('alokasiUnit').value = row.unit || '';
    document.getElementById('alokasiLokasi').value = row.lokasi || '';
    document.getElementById('alokasiHostname').value = row.hostname || '';
    document.getElementById('alokasiOctet').value = row.host_octet;

    const subnetSel = document.getElementById('alokasiSubnet');
    if (subnetSel && row.subnet) subnetSel.value = row.subnet;

    document.getElementById('alokasiTitle').textContent = 'Edit Alokasi Host IP Komputer Unit';

    updateAlokasiPreview();
    openModal('modal-alokasi-ip');
}

function setAlokasiStatus(statusEl, text, className) {
    if (!statusEl) return;

    statusEl.replaceChildren();
    statusEl.append(document.createTextNode('Status: '));

    const span = document.createElement('span');
    span.textContent = text;
    statusEl.append(span);

    statusEl.className = className;
}

function updateAlokasiPreview() {
    const prefix = currentSubnetPrefix();
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
        setAlokasiStatus(statusEl, '—', 'alokasi-status');
        return;
    }

    if (fullIpEl) fullIpEl.textContent = prefix + '.' + val;

    const currentIdEl = document.getElementById('alokasiId');
    const currentId = currentIdEl ? currentIdEl.value : '';
    const used = getKomputerData().find(r => Number(r.host_octet) === val && String(r.id) !== String(currentId));

    if (statusEl) {
        if (val >= 1 && val <= 9) {
            setAlokasiStatus(statusEl, 'Reserved (Core/Gateway)', 'alokasi-status is-core');
        } else if (used) {
            setAlokasiStatus(statusEl, 'Sudah Dipakai (' + used.hostname + ')', 'alokasi-status is-taken');
        } else if (val < 10 || val > 254) {
            setAlokasiStatus(statusEl, 'Di luar range valid', 'alokasi-status is-taken');
        } else {
            setAlokasiStatus(statusEl, 'Kosong & Tersedia', 'alokasi-status is-available');
        }
    }
}

function csvEscape(value) {
    let text = String(value ?? '');
    if (/^[=+\-@\t\r]/.test(text)) {
        text = "'" + text;
    }
    return '"' + text.replace(/"/g, '""') + '"';
}

function exportCsv() {
    const rows = getKomputerData();
    if (!rows.length) { showToast('Tidak ada data untuk diekspor.', 'error'); return; }

    const header = ['Nama Unit', 'Lokasi', 'Hostname', 'Alamat IP', 'Status'];
    const lines = [header.map(csvEscape).join(',')];

    rows.forEach(function (r) {
        const line = [
            r.unit || '',
            r.lokasi || '',
            r.hostname || '',
            r.ip || '',
            r.status || '',
        ].map(csvEscape);

        lines.push(line.join(','));
    });

    const blob = new Blob(['\uFEFF' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'alokasi-ip-jaringan.csv';
    a.click();
    URL.revokeObjectURL(url);
}