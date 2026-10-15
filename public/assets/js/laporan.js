document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        ['periode', 'filterStatus', 'filterAcc'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', () => filterForm.requestSubmit());
        });
    }

    // "Menampilkan ... Laporan": ubah jumlah baris lewat parameter GET
    const perPage = document.getElementById('perPage');
    const perPageValue = document.getElementById('perPageValue');
    if (perPage && perPageValue && filterForm) {
        perPage.addEventListener('change', function () {
            perPageValue.value = perPage.value;
            filterForm.requestSubmit();
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.classList.remove('open');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllCustomSelects();
            document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
        }
    });

    const unitSelect = document.getElementById('unit');
    if (unitSelect) {
        unitSelect.addEventListener('change', function () {
            updateJenisPoliVisibility();
            refreshBarangOptions();
        });
    }
    const jenisBarangSelect = document.getElementById('jenis_barang');
    if (jenisBarangSelect) {
        jenisBarangSelect.addEventListener('change', syncSerialOptions);
    }
    const noSeriSelect = document.getElementById('no_seri');
    if (noSeriSelect) {
        noSeriSelect.addEventListener('change', onNoSeriChange);
    }
    const incidentTime = document.getElementById('waktu_kejadian');
    if (incidentTime) {
        incidentTime.addEventListener('change', function () {
            if (incidentTime.value) {
                document.getElementById('tanggal').value = incidentTime.value.slice(0, 10);
            }
        });
    }

    // Dropdown custom (dengan kotak pencarian) untuk Unit & Jenis Barang.
    initCustomSelect('unit');
    initCustomSelect('jenis_barang');
    initCustomSelect('no_seri');
    initCustomSelect('jenis_poli', { searchable: false });
    initCustomSelect('status', { searchable: false });
    initCustomSelect('prioritas', { searchable: false });
    document.addEventListener('click', function () {
        closeAllCustomSelects();
    });

    initMonthPicker();
    initTableScrollSync();
});

function initTableScrollSync() {
    const tableScroll = document.getElementById('tableScroll');
    const tableHeadWrap = document.getElementById('tableHeadWrap');
    if (!tableScroll || !tableHeadWrap) return;

    function syncScrollbarPadding() {
        const scrollbarWidth = tableScroll.offsetWidth - tableScroll.clientWidth;
        tableHeadWrap.style.paddingRight = scrollbarWidth + 'px';
    }

    tableScroll.addEventListener('scroll', function () {
        tableHeadWrap.scrollLeft = tableScroll.scrollLeft;
    });

    syncScrollbarPadding();
    window.addEventListener('resize', syncScrollbarPadding);
    if ('ResizeObserver' in window) {
        new ResizeObserver(syncScrollbarPadding).observe(tableScroll);
    }
}

function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}


// Konfigurasi header modal laporan per mode (tambah/edit/lihat).
// Ini satu-satunya sumber teks judul, deskripsi, dan ikon — jangan set ulang di open*Modal().
const FORM_MODES = {
    create: { icon: 'note_add', title: 'Tambah Laporan Baru', desc: () => 'Input catatan kerusakan perangkat fasilitas rumah sakit' },
    edit: { icon: 'edit', title: 'Edit Laporan', desc: (id) => 'Perbarui data laporan #' + id },
    view: { icon: 'visibility', title: 'Detail Laporan', desc: (id) => 'Laporan #' + id + ' (hanya lihat)' },
};

function openTambahModal() {
    setFormMode('create');
    const form = document.getElementById('form-tambah-laporan');
    clearLaporanHistory();
    clearLegacyJenisPoliOption();
    form.reset();
    updateJenisPoliVisibility();
    refreshBarangOptions();
    document.getElementById('form-laporan-id').value = '';
    document.getElementById('waktu_kejadian').value = getCurrentLocalDateTime();
    setRecordedAt(null);
    document.getElementById('prioritas').value = 'Sedang';
    form.action = (window.BASE_URL || '') + '/laporan/simpan';

    refreshAllCustomSelects();
    openModal('modal-form-laporan');
}

function openEditModal(id) {
    const row = findLaporanById(id);
    if (!row) { alert('Data laporan tidak ditemukan.'); return; }

    setFormMode('edit', id);
    clearLaporanHistory();
    fillFormWithData(row);
    setRecordedAt(null);

    const form = document.getElementById('form-tambah-laporan');
    form.action = (window.BASE_URL || '') + '/laporan/update';

    openModal('modal-form-laporan');
}

function openDetailModal(id) {
    const row = findLaporanById(id);
    if (!row) { alert('Data laporan tidak ditemukan.'); return; }

    fillFormWithData(row);
    setFormMode('view', id);
    setRecordedAt(row.created_at || null);

    openModal('modal-form-laporan');
    loadLaporanHistory(id);
}

function clearLaporanHistory() {
    const history = document.getElementById('laporanHistory');
    const list = document.getElementById('laporanHistoryList');
    if (history) history.hidden = true;
    if (list) list.replaceChildren();
}

async function loadLaporanHistory(id) {
    const history = document.getElementById('laporanHistory');
    const list = document.getElementById('laporanHistoryList');
    if (!history || !list) return;

    history.hidden = false;
    list.replaceChildren();
    appendHistoryMessage(list, 'Memuat riwayat...');

    try {
        const response = await fetch((window.BASE_URL || '') + '/laporan/' + encodeURIComponent(id) + '/riwayat', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        });
        if (!(response.headers.get('content-type') || '').includes('application/json')) {
            throw new Error(response.redirected
                ? 'Sesi login berakhir. Silakan login kembali.'
                : 'Server mengembalikan respons riwayat yang tidak valid.');
        }
        const events = await response.json();
        if (!response.ok) {
            throw new Error(events.error || 'Riwayat laporan gagal dimuat.');
        }
        if (!Array.isArray(events)) {
            throw new Error('Format riwayat laporan tidak valid.');
        }

        list.replaceChildren();
        if (events.length === 0) {
            appendHistoryMessage(list, 'Belum ada riwayat yang tercatat sejak fitur ini diaktifkan.');
            return;
        }

        events.forEach(function (event) {
            const item = document.createElement('li');
            item.className = 'laporan-history__item';

            const action = document.createElement('p');
            action.className = 'laporan-history__action';
            action.textContent = event.aksi || 'Aktivitas';

            const meta = document.createElement('p');
            meta.className = 'laporan-history__meta';
            meta.textContent = (event.actor_name || 'Pengguna') + ' · ' + formatHistoryDate(event.created_at);

            const detail = document.createElement('p');
            detail.className = 'laporan-history__detail';
            detail.textContent = event.detail || '';

            item.append(action, meta, detail);
            list.appendChild(item);
        });
    } catch (error) {
        list.replaceChildren();
        appendHistoryMessage(list, error.message || 'Riwayat laporan gagal dimuat.');
    }
}

function appendHistoryMessage(list, message) {
    const item = document.createElement('li');
    item.className = 'laporan-history__detail';
    item.textContent = message;
    list.appendChild(item);
}

function formatHistoryDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/.exec(String(value || ''));
    if (!match) return String(value || '');
    const date = new Date(
        Number(match[1]),
        Number(match[2]) - 1,
        Number(match[3]),
        Number(match[4]),
        Number(match[5]),
        Number(match[6])
    );
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
}

function getCurrentLocalDateTime() {
    const now = new Date();
    const pad = value => String(value).padStart(2, '0');
    return now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate())
        + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
}

function setRecordedAt(value) {
    const wrapper = document.getElementById('laporanRecordedAt');
    const label = document.getElementById('laporanRecordedAtValue');
    if (!wrapper || !label) return;
    wrapper.hidden = !value;
    label.textContent = value ? formatHistoryDate(value) : '-';
}

function getLaporanData() {
    const el = document.getElementById('laporanDataJson');
    if (!el) return [];
    try { return JSON.parse(el.textContent); } catch (e) { return []; }
}

function findLaporanById(id) {
    return getLaporanData().find(r => Number(r.id) === Number(id));
}

function fillFormWithData(row) {
    document.getElementById('form-laporan-id').value = row.id;
    document.getElementById('tanggal').value = toDateInputValue(row.tanggal);
    document.getElementById('waktu_kejadian').value = toDateTimeLocalValue(row.waktu_kejadian);

    selectOptionByText('unit', row.urusan);
    updateJenisPoliVisibility();
    const jenisPoli = document.getElementById('jenis_poli');
    clearLegacyJenisPoliOption();
    const jenisPoliValue = row.jenis_poli || '';
    if (jenisPoliValue && !Array.from(jenisPoli.options).some(option => option.value === jenisPoliValue)) {
        const legacyOption = new Option(jenisPoliValue, jenisPoliValue);
        legacyOption.dataset.legacyPoli = 'true';
        jenisPoli.add(legacyOption);
    }
    jenisPoli.value = jenisPoliValue;

    refreshBarangOptions({ preserveValue: row.barang });
    const jenisBarang = document.getElementById('jenis_barang');
    const rowBarangName = String(row.barang || '').trim();
    if (rowBarangName && !hasOptionByText(jenisBarang, rowBarangName)) {
        jenisBarang.add(new Option(row.barang, row.barang_id || '', false, true));
    }
    selectOptionByText('jenis_barang', row.barang);

    syncSerialOptions();
    restoreSerialFromRow(row);

    document.getElementById('rincian_kerusakan').value = row.kerusakan || '';
    document.getElementById('uraian_kegiatan').value = row.uraian || '';

    const statusValue = row.status_penanganan
        || (row.hasil === 'selesai' ? 'Selesai' : 'Pending');
    document.getElementById('status').value = statusValue;

    document.getElementById('prioritas').value = row.prioritas || 'Sedang';

    refreshAllCustomSelects();
}

function toDateTimeLocalValue(value) {
    if (!value) return '';
    const match = /^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/.exec(String(value));
    return match ? match[1] + 'T' + match[2] : '';
}

function clearLegacyJenisPoliOption() {
    const jenisPoli = document.getElementById('jenis_poli');
    if (!jenisPoli) return;
    jenisPoli.querySelectorAll('option[data-legacy-poli="true"]').forEach(option => option.remove());
}

function updateJenisPoliVisibility() {
    const unit = document.getElementById('unit');
    const group = document.getElementById('jenis-poli-group');
    const jenisPoli = document.getElementById('jenis_poli');
    if (!unit || !group || !jenisPoli) return;

    const selectedUnit = unit.options[unit.selectedIndex];
    const isPoli = selectedUnit && selectedUnit.textContent.trim().toUpperCase() === 'POLI';
    group.style.display = isPoli ? 'flex' : 'none';
    group.setAttribute('aria-hidden', String(!isPoli));
    jenisPoli.disabled = !isPoli;
    jenisPoli.required = Boolean(isPoli);
    if (!isPoli) jenisPoli.value = '';
    refreshCustomSelect('jenis_poli');
}

function formatUnitLocation(row) {
    return [row.urusan, row.jenis_poli].filter(Boolean).join(' - ');
}

function selectOptionByText(selectId, text) {
    const select = document.getElementById(selectId);
    if (!select || !text) return;
    const match = Array.from(select.options).find(
        opt => opt.textContent.trim().toLowerCase() === String(text).trim().toLowerCase()
    );
    select.value = match ? match.value : '';
}

function getAsetData() {
    const el = document.getElementById('asetDataJson');
    if (!el) return [];
    try { return JSON.parse(el.textContent); } catch (e) { return []; }
}

// Aset boleh tanpa serial (NULL). Label pakai serial, fallback ke tipe lalu "Aset #id";
// value unik agar tetap bisa dipilih di dropdown.
function asetSerialLabel(aset) {
    const sn = aset && aset.serial_number != null ? String(aset.serial_number).trim() : '';
    if (sn !== '') return sn;
    const tipe = aset && aset.tipe != null ? String(aset.tipe).trim() : '';
    return tipe !== '' ? tipe : ('Aset #' + aset.id);
}

function asetSerialValue(aset) {
    const sn = aset && aset.serial_number != null ? String(aset.serial_number).trim() : '';
    return sn !== '' ? sn : ('#' + aset.id);
}

function refreshBarangOptions(options) {
    options = options || {};
    const preserveValue = options.preserveValue != null ? String(options.preserveValue).trim() : '';

    const select = document.getElementById('jenis_barang');
    const unit = document.getElementById('unit');
    const hint = document.getElementById('barang-filter-hint');
    if (!select || !unit) return;

    if (!select._fullOptions) select._fullOptions = Array.from(select.options);

    const unitId = Number(unit.value || 0);
    const asets = unitId ? getAsetData().filter(a => Number(a.id_ruangan) === unitId) : [];
    const byBarang = new Map();
    asets.forEach(a => {
        if (!byBarang.has(String(a.id_barang))) byBarang.set(String(a.id_barang), a.nama_barang);
    });
    const hasAssets = byBarang.size > 0;

    select.replaceChildren();
    const placeholder = new Option('Pilih Jenis Barang', '');
    placeholder.disabled = true;
    placeholder.selected = true;
    select.add(placeholder);

    if (hasAssets) {
        // Hanya jenis barang yang terdaftar sebagai aset di ruangan ini.
        byBarang.forEach((name, idBarang) => select.add(new Option(name, idBarang)));
        select.disabled = false;
    } else if (preserveValue !== '') {
        // Mode edit laporan lama: pertahankan jenis barang yang sudah tersimpan.
        const match = select._fullOptions.find(
            o => o.textContent.trim().toLowerCase() === preserveValue.toLowerCase()
        );
        select.add(new Option(preserveValue, match ? match.value : '', false, true));
        select.disabled = false;
    } else {
        // Ruangan tanpa aset (mode tambah): kunci dropdown, minta tambah aset dulu.
        placeholder.textContent = unitId ? 'Belum ada aset di ruangan ini' : 'Pilih Jenis Barang';
        select.disabled = true;
    }

    if (hint) {
        if (hasAssets) {
            hint.textContent = 'Hanya menampilkan jenis barang yang terdaftar di ruangan ini.';
            hint.style.display = 'block';
        } else if (unitId && preserveValue === '') {
            hint.innerHTML = 'Ruangan ini belum punya aset terdaftar. Tambahkan asetnya dulu di '
                + '<a href="' + (window.BASE_URL || '') + '/unit">menu Unit &amp; Barang</a>.';
            hint.style.display = 'block';
        } else {
            hint.style.display = 'none';
        }
    }

    clearSerialGroup();
    refreshCustomSelect('jenis_barang');
    refreshCustomSelect('no_seri');
}

function clearSerialGroup() {
    const noSeri = document.getElementById('no_seri');
    const tipe = document.getElementById('tipe_barang');
    const asetId = document.getElementById('aset_id');
    if (noSeri) resetNoSeriSelect(noSeri);
    if (tipe) { tipe.value = ''; tipe.readOnly = false; tipe.classList.remove('is-readonly'); }
    if (asetId) asetId.value = '';
}

function resetNoSeriSelect(noSeri) {
    noSeri.replaceChildren();
    const placeholder = new Option('Pilih No Seri', '');
    placeholder.disabled = true;
    placeholder.selected = true;
    noSeri.add(placeholder);
}

function syncSerialOptions() {
    const unit = document.getElementById('unit');
    const barang = document.getElementById('jenis_barang');
    const noSeri = document.getElementById('no_seri');
    if (!noSeri) return;

    resetNoSeriSelect(noSeri);
    clearTipeGroup();
    noSeri.required = false;

    if (!unit || !barang) return;
    const unitId = Number(unit.value || 0);
    const barangId = Number(barang.value || 0);
    if (!unitId || !barangId) return;

    const asets = getAsetData().filter(a =>
        Number(a.id_ruangan) === unitId && Number(a.id_barang) === barangId
    );
    asets.forEach(a => {
        const opt = new Option(asetSerialLabel(a), asetSerialValue(a));
        opt.dataset.id_aset = String(a.id);
        opt.dataset.tipe = a.tipe || '';
        noSeri.add(opt);
    });

    // Wajib pilih serial bila ada aset; laporan lama tanpa aset tidak dipaksa.
    noSeri.required = asets.length > 0;
    refreshCustomSelect('no_seri');
}

function clearTipeGroup() {
    const tipe = document.getElementById('tipe_barang');
    const asetId = document.getElementById('aset_id');
    if (tipe) { tipe.value = ''; tipe.readOnly = false; tipe.classList.remove('is-readonly'); }
    if (asetId) asetId.value = '';
}

function onNoSeriChange() {
    const select = document.getElementById('no_seri');
    const tipe = document.getElementById('tipe_barang');
    const asetId = document.getElementById('aset_id');
    if (!select || !tipe || !asetId) return;

    const option = select.options[select.selectedIndex];
    const isAsetOption = option && option.dataset && option.dataset.id_aset;

    if (isAsetOption) {
        asetId.value = option.dataset.id_aset;
        tipe.value = option.dataset.tipe || '';
        tipe.readOnly = true;
        tipe.classList.add('is-readonly');
    } else {
        asetId.value = '';
        tipe.readOnly = false;
        tipe.classList.remove('is-readonly');
    }
}

function restoreSerialFromRow(row) {
    const noSeri = document.getElementById('no_seri');
    const tipe = document.getElementById('tipe_barang');
    const asetId = document.getElementById('aset_id');
    if (!noSeri) return;

    resetNoSeriSelect(noSeri);

    // Prioritaskan tautan lewat id_aset agar serial apa pun (termasuk "-") tetap
    // terpulihkan, tanpa bergantung pada teks serial.
    const asetIdNum = Number(row.id_aset);
    const aset = asetIdNum
        ? getAsetData().find(a => Number(a.id) === asetIdNum)
        : null;

    if (aset) {
        const opt = new Option(asetSerialLabel(aset), asetSerialValue(aset));
        opt.dataset.id_aset = String(aset.id);
        opt.dataset.tipe = aset.tipe || '';
        noSeri.add(opt);
        noSeri.value = asetSerialValue(aset);
        if (asetId) asetId.value = String(aset.id);
        if (tipe) {
            tipe.value = aset.tipe || '';
            tipe.readOnly = true;
            tipe.classList.add('is-readonly');
        }
        return;
    }

    // Laporan lama (sebelum wajib memilih aset): tampilkan serial yang tersimpan.
    const serial = row.serial_number != null ? String(row.serial_number).trim() : '';
    if (serial !== '') {
        noSeri.add(new Option(serial, serial));
        noSeri.value = serial;
    }
    if (asetId) asetId.value = '';
    if (tipe) {
        tipe.value = serial !== '' ? (row.tipe_barang || '') : '';
        tipe.readOnly = false;
        tipe.classList.remove('is-readonly');
    }
}

function hasOptionByText(select, text) {
    const needle = String(text).trim().toLowerCase();
    return Array.from(select.options).some(opt => opt.textContent.trim().toLowerCase() === needle);
}

function toDateInputValue(rawDate) {
    if (!rawDate) return '';
    const d = new Date(rawDate);
    if (isNaN(d.getTime())) return '';
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

function setFormMode(mode, id) {
    // Header (ikon/judul/deskripsi) ikut berubah sesuai mode.
    const config = FORM_MODES[mode] || FORM_MODES.create;
    document.getElementById('formLaporanIcon').textContent = config.icon;
    document.getElementById('formLaporanTitle').textContent = config.title;
    document.getElementById('formLaporanDesc').textContent = config.desc(id);

    const form = document.getElementById('form-tambah-laporan');
    const isView = mode === 'view';

    form.querySelectorAll('input, select, textarea').forEach(function (field) {
        if (field.id === 'form-laporan-id') return; // hidden id, selalu aktif
        field.disabled = isView;
    });

    document.getElementById('formLaporanActions').style.display = isView ? 'none' : 'flex';
    document.getElementById('formLaporanViewActions').style.display = isView ? 'flex' : 'none';
    const history = document.getElementById('laporanHistory');
    if (history && !isView) history.hidden = true;

    refreshAllCustomSelects();
}

function confirmDelete(id) {
    openConfirmModal({
        icon: 'delete',
        title: 'Hapus Barang',
        text: 'Yakin hapus laporan #' + id + '? Tindakan ini tidak bisa dibatalkan.',
        confirmLabel: 'Ya, Hapus',
        variant: 'red',
        onConfirm: function () {
            postAction((window.BASE_URL || '') + '/laporan/hapus/' + id);
        }
    });
}

function terimaBarang(id) {
    openConfirmModal({
        icon: 'inventory_2',
        title: 'Terima Barang',
        text: 'Tandai laporan #' + id + ' sebagai sudah diterima kembali dari vendor/service?',
        confirmLabel: 'Ya, Terima',
        variant: 'green',
        onConfirm: function () {
            postAction((window.BASE_URL || '') + '/laporan/terima/' + id);
        }
    });
}

function kirimBarang(id) {
    openConfirmModal({
        icon: 'local_shipping',
        title: 'Kirim Barang',
        text: 'Tandai laporan #' + id + ' sebagai sudah dikirim ke vendor/service?',
        confirmLabel: 'Ya, Kirim',
        onConfirm: function () {
            postAction((window.BASE_URL || '') + '/laporan/kirim/' + id);
        }
    });
}

function openConfirmModal({ icon, title, text, confirmLabel, variant = 'primary', onConfirm }) {
    const iconEl = document.getElementById('confirmIcon');
    const iconWrap = iconEl.parentElement;

    iconEl.textContent = icon || 'help';
    document.getElementById('confirmTitle').textContent = title || 'Konfirmasi';
    document.getElementById('confirmText').textContent = text || 'Apakah kamu yakin?';

    iconWrap.classList.remove('confirm-card__icon--green');
    if (variant === 'green') {
        iconWrap.classList.add('confirm-card__icon--green');
    }

    iconWrap.classList.remove('confirm-card__icon--red');
    if (variant === 'red') {
        iconWrap.classList.add('confirm-card__icon--red');
    }

    const oldBtn = document.getElementById('confirmActionBtn');
    oldBtn.textContent = confirmLabel || 'Ya, Lanjutkan';

    let buttonClass = 'confirm-btn--primary';

    if (variant === 'green') {
        buttonClass = 'confirm-btn--success';
    } else if (variant === 'red') {
        buttonClass = 'confirm-btn--danger';
    }

    oldBtn.className = 'confirm-btn ' + buttonClass;

    const freshBtn = oldBtn.cloneNode(true);
    oldBtn.parentNode.replaceChild(freshBtn, oldBtn);
    freshBtn.addEventListener('click', function () {
        closeModal('modal-konfirmasi');
        onConfirm();
    });

    openModal('modal-konfirmasi');
}

function initMonthPicker() {
    const wrapper = document.getElementById('periodePicker');
    const trigger = document.getElementById('periodeTrigger');
    const panel = document.getElementById('periodePanel');
    const yearLabel = document.getElementById('periodeYearLabel');
    const grid = document.getElementById('periodeGrid');
    const hiddenInput = document.getElementById('periodeValue');
    const prevYearBtn = document.getElementById('periodePrevYear');
    const nextYearBtn = document.getElementById('periodeNextYear');
    const filterForm = document.getElementById('filterForm');

    if (!wrapper || !trigger || !panel || !grid || !hiddenInput) return;

    const namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    const [selectedYearStr, selectedMonthStr] = hiddenInput.value.split('-');
    let selectedYear = parseInt(selectedYearStr, 10) || new Date().getFullYear();
    let selectedMonth = parseInt(selectedMonthStr, 10) || (new Date().getMonth() + 1);
    let viewYear = selectedYear;

    function renderGrid() {
        yearLabel.textContent = viewYear;
        grid.innerHTML = '';
        namaBulan.forEach(function (nama, idx) {
            const monthNum = idx + 1;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'month-picker__month';
            btn.textContent = nama.slice(0, 3);
            if (viewYear === selectedYear && monthNum === selectedMonth) {
                btn.classList.add('is-selected');
            }
            btn.addEventListener('click', function () {
                selectedYear = viewYear;
                selectedMonth = monthNum;
                applySelection();
            });
            grid.appendChild(btn);
        });
    }

    function applySelection() {
        hiddenInput.value = String(selectedYear) + '-' + String(selectedMonth).padStart(2, '0');
        trigger.textContent = namaBulan[selectedMonth - 1] + ' ' + selectedYear;
        closePanel();
        if (filterForm) filterForm.requestSubmit();
    }

    function openPanel() {
        viewYear = selectedYear;
        renderGrid();
        wrapper.classList.add('is-open');
    }

    function closePanel() {
        wrapper.classList.remove('is-open');
    }

    // Seluruh kotak (ikon + teks + chevron) bisa dipencet, bukan cuma teksnya
    const box = wrapper.querySelector('.month-picker__input') || trigger;
    box.addEventListener('click', function (e) {
        e.stopPropagation();
        wrapper.classList.contains('is-open') ? closePanel() : openPanel();
    });

    prevYearBtn.addEventListener('click', function () {
        viewYear -= 1;
        renderGrid();
    });

    nextYearBtn.addEventListener('click', function () {
        viewYear += 1;
        renderGrid();
    });

    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) closePanel();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePanel();
    });
}
