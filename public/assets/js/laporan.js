document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        ['periode', 'filterStatus'].forEach(function (id) {
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

// ============================================================
// CUSTOM SEARCHABLE SELECT
// Mengganti tampilan <select> native (Unit & Jenis Barang) dengan dropdown
// yang punya kotak pencarian dan daftar ber-scroll (maks ±6 baris).
// <select> aslinya tetap ada (display:none) sebagai sumber data, jadi seluruh
// logika lain (value/options/change) tidak perlu diubah.
// ============================================================

const CUSTOM_SELECTS = {};

function initCustomSelect(selectId, options) {
    options = options || {};
    const searchable = options.searchable !== false;

    const select = document.getElementById(selectId);
    if (!select || select.dataset.csReady === '1') return;
    select.dataset.csReady = '1';

    const wrapper = select.closest('.input-icon-wrapper') || select.parentElement;
    const placeholderOpt  = Array.from(select.options).find(o => o.disabled && o.value === '');
    const placeholderText = (placeholderOpt ? placeholderOpt.textContent : 'Pilih...').trim();

    const root = document.createElement('div');
    root.className = 'cs';
    root.dataset.csFor = selectId;

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'cs__trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const valueLabel = document.createElement('span');
    valueLabel.className = 'cs__value';

    const chevron = document.createElement('span');
    chevron.className = 'material-symbols-outlined cs__chevron';
    chevron.textContent = 'expand_more';

    trigger.append(valueLabel, chevron);

    const panel = document.createElement('div');
    panel.className = 'cs__panel';
    panel.dataset.csPanelFor = selectId;

    const list = document.createElement('ul');
    list.className = 'cs__list';
    list.setAttribute('role', 'listbox');

    const empty = document.createElement('div');
    empty.className = 'cs__empty';
    empty.textContent = 'Tidak ada hasil';

    // Kotak pencarian opsional; otomatis disembunyikan bila opsi sedikit (lihat buildList).
    let search = null;
    let searchWrap = null;
    if (searchable) {
        searchWrap = document.createElement('div');
        searchWrap.className = 'cs__search-wrap';
        const searchIcon = document.createElement('span');
        searchIcon.className = 'material-symbols-outlined';
        searchIcon.textContent = 'search';
        search = document.createElement('input');
        search.type = 'text';
        search.className = 'cs__search';
        search.placeholder = 'Cari...';
        search.autocomplete = 'off';
        searchWrap.append(searchIcon, search);
        panel.appendChild(searchWrap);
    }

    panel.append(list, empty);
    root.append(trigger);

    select.style.display = 'none';
    wrapper.appendChild(root);
    // Panel dipindah ke <body> + position:fixed supaya tidak terpotong oleh
    // overflow kartu modal dan selalu muncul tepat di bawah trigger.
    document.body.appendChild(panel);

    let highlight = -1;
    let isOpen = false;

    function isPlaceholderValue(v) {
        return v === '' || v == null;
    }

    function visibleOptions() {
        return Array.from(list.querySelectorAll('.cs__option')).filter(el => el.style.display !== 'none');
    }

    function setHighlight(index) {
        const opts = visibleOptions();
        opts.forEach(el => el.classList.remove('is-highlight'));
        if (opts.length === 0) { highlight = -1; return; }
        highlight = ((index % opts.length) + opts.length) % opts.length;
        opts[highlight].classList.add('is-highlight');
        opts[highlight].scrollIntoView({ block: 'nearest' });
    }

    function buildList() {
        list.replaceChildren();
        Array.from(select.options).forEach(function (opt) {
            if (opt.disabled && isPlaceholderValue(opt.value)) return; // lewati placeholder
            const li = document.createElement('li');
            li.className = 'cs__option';
            li.setAttribute('role', 'option');
            li.dataset.value = opt.value;
            li.textContent = opt.textContent.trim();
            li.addEventListener('click', function () {
                select.value = opt.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                close();
                sync();
            });
            list.appendChild(li);
        });
        // Sembunyikan kotak cari bila opsinya sedikit (muat tanpa scroll/search).
        if (searchWrap) {
            searchWrap.style.display = list.querySelectorAll('.cs__option').length > 6 ? '' : 'none';
        }
        highlight = -1;
    }

    function applyFilter() {
        const q = searchable ? search.value.trim().toLowerCase() : '';
        let shown = 0;
        Array.from(list.querySelectorAll('.cs__option')).forEach(function (li) {
            const match = q === '' || li.textContent.toLowerCase().includes(q);
            li.style.display = match ? '' : 'none';
            if (match) shown++;
        });
        empty.style.display = shown === 0 ? 'block' : 'none';
        clearHighlight();
    }

    function clearHighlight() {
        list.querySelectorAll('.cs__option.is-highlight').forEach(function (el) {
            el.classList.remove('is-highlight');
        });
        highlight = -1;
    }

    function sync() {
        const val = select.value;
        const opt = Array.from(select.options).find(o => o.value === val && !(o.disabled && isPlaceholderValue(o.value)));
        const hasValue = Boolean(opt) && !isPlaceholderValue(val);
        // Placeholder dibaca live agar pesannya bisa diganti dinamis (mis. saat ruangan tanpa aset).
        const phOpt = Array.from(select.options).find(o => o.disabled && isPlaceholderValue(o.value));
        const ph = phOpt ? phOpt.textContent.trim() : placeholderText;

        valueLabel.textContent = hasValue ? opt.textContent.trim() : ph;
        valueLabel.classList.toggle('is-placeholder', !hasValue);

        Array.from(list.querySelectorAll('.cs__option')).forEach(function (li) {
            li.classList.toggle('is-selected', hasValue && li.dataset.value === val);
        });

        const disabled = select.disabled;
        root.classList.toggle('is-disabled', disabled);
        trigger.disabled = disabled;
        trigger.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        if (disabled) close();
    }

    function positionPanel() {
        const r = trigger.getBoundingClientRect();
        const gap = 6;
        panel.style.width = r.width + 'px';
        panel.style.left  = r.left + 'px';

        const spaceBelow = window.innerHeight - r.bottom - gap;
        const spaceAbove = r.top - gap;
        const openUp = spaceBelow < 300 && spaceAbove > spaceBelow;

        panel.classList.toggle('cs__panel--up', openUp);
        if (openUp) {
            panel.style.top    = 'auto';
            panel.style.bottom = (window.innerHeight - r.top + gap) + 'px';
        } else {
            panel.style.bottom = 'auto';
            panel.style.top    = (r.bottom + gap) + 'px';
        }
    }

    function onReposition() {
        if (isOpen) positionPanel();
    }

    function open() {
        if (select.disabled) return;
        closeAllCustomSelects();
        if (search) search.value = '';
        applyFilter();
        isOpen = true;
        root.classList.add('is-open');
        panel.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        positionPanel();
        window.addEventListener('scroll', onReposition, true);
        window.addEventListener('resize', onReposition);
        if (search) search.focus();
    }

    function close() {
        isOpen = false;
        root.classList.remove('is-open');
        panel.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        window.removeEventListener('scroll', onReposition, true);
        window.removeEventListener('resize', onReposition);
    }

    trigger.addEventListener('click', function (e) {
        e.stopPropagation();
        isOpen ? close() : open();
    });
    panel.addEventListener('click', function (e) { e.stopPropagation(); });

    function onKeydown(e) {
        if (e.key === 'Escape') {
            if (!isOpen) return;
            e.stopPropagation();
            close();
            trigger.focus();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!isOpen) { open(); return; }
            setHighlight(highlight < 0 ? 0 : highlight + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (!isOpen) { open(); return; }
            setHighlight(highlight < 0 ? visibleOptions().length - 1 : highlight - 1);
        } else if (e.key === 'Enter') {
            if (!isOpen) return; // biarkan tombol trigger membuka (klik default)
            e.preventDefault();
            const opts = visibleOptions();
            if (opts.length) opts[highlight < 0 ? 0 : highlight].click();
        }
    }

    if (search) {
        search.addEventListener('input', applyFilter);
        search.addEventListener('keydown', onKeydown);
    }
    trigger.addEventListener('keydown', onKeydown);

    buildList();
    sync();

    root._csRebuild = buildList;
    root._csSync    = sync;

    CUSTOM_SELECTS[selectId] = { close: close };
}

/** Bangun ulang daftar + sinkronkan tampilan setelah <select> diubah kode lain. */
function refreshCustomSelect(selectId) {
    const root = document.querySelector('.cs[data-cs-for="' + selectId + '"]');
    if (!root) return;
    if (root._csRebuild) root._csRebuild();
    if (root._csSync) root._csSync();
}

function refreshAllCustomSelects() {
    ['unit', 'jenis_barang', 'no_seri', 'jenis_poli', 'status', 'prioritas'].forEach(refreshCustomSelect);
}

function closeAllCustomSelects() {
    Object.keys(CUSTOM_SELECTS).forEach(function (id) {
        CUSTOM_SELECTS[id].close();
    });
}

// Konfigurasi header modal laporan per mode (tambah/edit/lihat).
// Ini satu-satunya sumber teks judul, deskripsi, dan ikon — jangan set ulang di open*Modal().
const FORM_MODES = {
    create: { icon: 'note_add',   title: 'Tambah Laporan Baru', desc: () => 'Input catatan kerusakan perangkat fasilitas rumah sakit' },
    edit:   { icon: 'edit',       title: 'Edit Laporan',        desc: (id) => 'Perbarui data laporan #' + id },
    view:   { icon: 'visibility', title: 'Detail Laporan',      desc: (id) => 'Laporan #' + id + ' (hanya lihat)' },
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
    document.getElementById('formLaporanIcon').textContent  = config.icon;
    document.getElementById('formLaporanTitle').textContent = config.title;
    document.getElementById('formLaporanDesc').textContent  = config.desc(id);

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
    const wrapper   = document.getElementById('periodePicker');
    const trigger   = document.getElementById('periodeTrigger');
    const panel     = document.getElementById('periodePanel');
    const yearLabel = document.getElementById('periodeYearLabel');
    const grid      = document.getElementById('periodeGrid');
    const hiddenInput = document.getElementById('periodeValue');
    const prevYearBtn = document.getElementById('periodePrevYear');
    const nextYearBtn = document.getElementById('periodeNextYear');
    const filterForm = document.getElementById('filterForm');

    if (!wrapper || !trigger || !panel || !grid || !hiddenInput) return;

    const namaBulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    const [selectedYearStr, selectedMonthStr] = hiddenInput.value.split('-');
    let selectedYear  = parseInt(selectedYearStr, 10) || new Date().getFullYear();
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

// ============================================================
// SURAT KERUSAKAN MODAL
// ============================================================

let _suratCurrentId = null;
let _suratIsReprint = false;

// Konfigurasi header modal surat per mode:
//   form    → form isian surat (sebelum TTE diterbitkan)
//   preview → dokumen surat jadi ber-TTE, siap dicetak
// Ini satu-satunya sumber teks/ikon header surat — jangan set ulang di tempat lain.
const SURAT_MODES = {
    form: {
        icon: 'edit_note',
        title: 'Form Surat Kerusakan',
        desc: 'Isi form di bawah untuk menerbitkan QR Code TTE dan surat kerusakan',
        badge: null,
    },
    preview: {
        icon: 'description',
        title: 'Surat Kerusakan Resmi (TTE)',
        desc: 'Dokumen surat pengantar kerusakan ber-TTE yang siap dicetak',
        badge: 'Surat Terbit (TTE)',
    },
};

/**
 * Atur tampilan modal surat sesuai mode ('form' atau 'preview'):
 * header, badge, section yang tampil, dan status tombol cetak.
 */
function setSuratMode(mode) {
    const config = SURAT_MODES[mode] || SURAT_MODES.form;
    const isPreview = mode === 'preview';

    const setText = (id, text) => {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    };
    setText('suratModalHeaderIcon', config.icon);
    setText('suratModalHeaderTitle', config.title);
    setText('suratModalHeaderDesc', config.desc);

    const badge = document.getElementById('suratReprintBadge');
    if (badge) {
        badge.textContent = config.badge || '';
        badge.style.display = config.badge ? 'flex' : 'none';
    }

    const approval = document.getElementById('suratApprovalCheck');
    if (approval) approval.checked = true;

    const btnCetak = document.getElementById('suratBtnCetak');
    if (btnCetak) btnCetak.disabled = false;

    const formSection = document.getElementById('suratFormSection');
    const previewSection = document.getElementById('suratDocumentPreviewSection');
    if (formSection) formSection.style.display = isPreview ? 'none' : 'block';
    if (previewSection) previewSection.style.display = isPreview ? 'block' : 'none';
}

function openSuratModal(id, isReprint = false) {
    const row = findLaporanById(id);
    if (!row) { alert('Data laporan tidak ditemukan.'); return; }

    _suratCurrentId = id;

    // Jika nama_pelapor sudah tersimpan di DB ATAU tombol "Lihat Surat" diklik,
    // langsung tampilkan Dokumen Surat Jadi (TTE Preview) tanpa minta isi nama lagi.
    const hasSavedSurat = Boolean(row.nama_pelapor && String(row.nama_pelapor).trim() !== '');
    const shouldShowPreview = Boolean(isReprint || hasSavedSurat);
    _suratIsReprint = shouldShowPreview;

    // Isi info laporan
    document.getElementById('suratLaporanId').textContent = id;
    document.getElementById('suratInfoDetail').textContent =
        ' — ' + (row.barang || '?') + ' | ' + (formatUnitLocation(row) || '?');

    // Tanggal surat & nomor
    const today = new Date();
    const todayStr = today.toISOString().slice(0, 10);
    const tglSuratVal = row.tgl_surat || todayStr;
    document.getElementById('suratTglSurat').value = tglSuratVal;

    const bulanRomawi = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    const bln = bulanRomawi[today.getMonth()];
    const thn = today.getFullYear();
    const nomorVal = row.nomor_surat || `${String(id).padStart(3,'0')}/IT/${bln}/${thn}`;
    document.getElementById('suratNomor').value = nomorVal;

    // Tidak auto-fill nama/jabatan agar user isi manual
    const namaVal = (row.nama_pelapor && String(row.nama_pelapor).trim() !== '') ? row.nama_pelapor : '';
    const jabVal  = row.jabatan_pelapor || '';

    document.getElementById('suratNamaPelapor').value = namaVal;
    document.getElementById('suratJabatan').value = jabVal;
    document.getElementById('suratKeterangan').value = row.keterangan_tambahan || '';

    // Kode TTD & URL verifikasi diturunkan dari verify_token tersimpan
    const verifyToken = String(row.verify_token || '');
    const ttdCode = buildTtdCode(id, verifyToken);
    document.getElementById('suratTtdCode').textContent = ttdCode;

    // URL verifikasi QR Code
    const host = window.location.origin;
    const verifyUrl = `${host}${window.BASE_URL || ''}/surat/verifikasi?token=${encodeURIComponent(verifyToken)}`;
    const logoUrl = `${host}${window.BASE_URL || ''}/assets/images/logo_alhuda_kop.png`;

    // Render QR Code TTE dengan logo RS Al-Huda
    const qrCanvas = document.getElementById('suratQrCanvas');
    if (qrCanvas && typeof window.drawQrWithLogo === 'function') {
        window.drawQrWithLogo(qrCanvas, verifyUrl, logoUrl, function(dataUrl) {
            const pvQrImg = document.getElementById('pvSuratQrImg');
            if (pvQrImg) pvQrImg.src = dataUrl;
        });
    }

    // Update elemen paper view (Surat Jadi)
    updateSuratPaperView({
        id,
        nomorVal,
        tglSuratVal,
        row,
        namaVal,
        jabVal,
        ketVal: document.getElementById('suratKeterangan').value.trim(),
        ttdCode
    });

    // Sesuaikan header modal & tampilan section
    setSuratMode(shouldShowPreview ? 'preview' : 'form');

    openModal('modal-surat-kerusakan');
}

function updateSuratPaperView({ id, nomorVal, tglSuratVal, row, namaVal, jabVal, ketVal, ttdCode }) {
    if (document.getElementById('pvSuratNomor')) document.getElementById('pvSuratNomor').textContent = nomorVal || '—';
    if (document.getElementById('pvSuratTgl')) document.getElementById('pvSuratTgl').textContent = formatTglIndo(tglSuratVal);
    if (document.getElementById('pvSuratId')) document.getElementById('pvSuratId').textContent = id;
    if (document.getElementById('pvSuratBarang')) document.getElementById('pvSuratBarang').textContent = row.barang || '—';
    if (document.getElementById('pvSuratRuangan')) document.getElementById('pvSuratRuangan').textContent = formatUnitLocation(row) || '—';
    if (document.getElementById('pvSuratSn')) document.getElementById('pvSuratSn').textContent = row.serial_number || '—';
    if (document.getElementById('pvSuratTglLaporan')) document.getElementById('pvSuratTglLaporan').textContent = formatTglIndo(row.tanggal);
    if (document.getElementById('pvSuratStatus')) document.getElementById('pvSuratStatus').textContent = row.status_penanganan || 'Pending';
    if (document.getElementById('pvSuratRincian')) document.getElementById('pvSuratRincian').textContent = row.kerusakan || '—';
    if (document.getElementById('pvSuratUraian')) document.getElementById('pvSuratUraian').textContent = row.uraian || '—';
    if (document.getElementById('pvSuratKeterangan')) document.getElementById('pvSuratKeterangan').textContent = ketVal || '(tidak ada keterangan tambahan)';
    if (document.getElementById('pvSuratTtdCode')) document.getElementById('pvSuratTtdCode').textContent = ttdCode;
    if (document.getElementById('pvSuratNamaPelapor')) document.getElementById('pvSuratNamaPelapor').textContent = namaVal || 'Petugas Unit IT';
    if (document.getElementById('pvSuratJabatan')) document.getElementById('pvSuratJabatan').textContent = jabVal || 'Penanggung Jawab / Staf IT';
}

function closeSuratModal() {
    closeModal('modal-surat-kerusakan');
    _suratCurrentId = null;
    _suratIsReprint = false;
}

function onSuratApprovalChange() {
    const checked = document.getElementById('suratApprovalCheck').checked;
    const btnCetak = document.getElementById('suratBtnCetak');
    if (btnCetak) btnCetak.disabled = !checked;
}



/**
 * Bangun kode TTD yang ditampilkan dari verify_token yang tersimpan di server.
 */
function buildTtdCode(id, token) {
    const suffix = String(token || '').slice(0, 6).toUpperCase() || 'XXXXXX';
    return `LPR${String(id).padStart(4, '0')}-TTE-${suffix}`;
}

/**
 * Cetak surat kerusakan:
 * 1. Validasi form
 * 2. Bangun HTML surat dengan QR Code Berlogo
 * 3. Buka window print baru
 * 4. Kirim POST /laporan/surat/{id} untuk simpan & update status
 */
function cetakSurat() {
    let namaPelapor = document.getElementById('suratNamaPelapor').value.trim();
    let jabatan     = document.getElementById('suratJabatan').value.trim();
    let tglSurat    = document.getElementById('suratTglSurat').value;
    const nomor       = document.getElementById('suratNomor').value.trim();
    const keterangan  = document.getElementById('suratKeterangan').value.trim();
    const ttdCode     = document.getElementById('suratTtdCode').textContent;
    const isReprint   = _suratIsReprint;

    if (!namaPelapor) { alert('Nama Pelapor wajib diisi.'); document.getElementById('suratNamaPelapor').focus(); return; }
    if (!jabatan)     { alert('Jabatan/Bagian wajib diisi.'); document.getElementById('suratJabatan').focus(); return; }
    if (!tglSurat)    { alert('Tanggal Surat wajib diisi.'); document.getElementById('suratTglSurat').focus(); return; }
    namaPelapor = namaPelapor || 'Petugas Unit IT';
    jabatan     = jabatan || 'Penanggung Jawab / Staf IT';
    tglSurat    = tglSurat || new Date().toISOString().slice(0, 10);

    const id  = _suratCurrentId;
    const row = findLaporanById(id);
    if (!row) return;

    const tglFmt = formatTglIndo(tglSurat);

    const canvas = document.getElementById('suratQrCanvas');
    const qrImg = canvas ? canvas.toDataURL('image/png') : '';

    const html = buildSuratHtml({
        nomor, tglFmt, namaPelapor, jabatan, keterangan, ttdCode, qrImg, row, id
    });

    const pw = window.open('', '_blank', 'width=900,height=700');
    if (!pw) { alert('Pop-up diblokir browser. Izinkan pop-up untuk mencetak.'); return; }
    pw.document.write(html);
    pw.document.close();
    pw.onload = function() {
        pw.focus();
        pw.print();
    };

    postAction((window.BASE_URL || '') + '/laporan/surat/' + id, {
        nama_pelapor: namaPelapor,
        jabatan_pelapor: jabatan,
        nomor_surat: nomor,
        tgl_surat: tglSurat,
        mode: isReprint ? 'reprint' : 'kirim'
    });
    // Modal tetap bisa ditutup user atau setelah postAction redirect; biar tetap responsif, tutup duluan opsional
    closeSuratModal();
}

function buildSuratHtml({ nomor, tglFmt, namaPelapor, jabatan, keterangan, ttdCode, qrImg, row, id }) {
    const esc = (s) => String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const logoSrc = `${window.location.origin}${window.BASE_URL || ''}/assets/images/logo_alhuda_kop.png`;

    return `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Kerusakan #${id}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #111; background: #fff; padding: 20mm 25mm; }
  .kop { position: relative; display: flex; align-items: center; justify-content: center; min-height: 96px; border-bottom: 3px double #00288e; padding: 0 0 12px 82px; margin-bottom: 16px; text-align: center; }
  .kop-logo { position: absolute; left: 0; top: 0; width: 70px; height: 82px; object-fit: contain; }
  .kop-text { width: 100%; text-align: center; }
  .kop-text h1 { font-size: 16pt; color: #00288e; font-weight: bold; letter-spacing: 0.5px; margin-bottom: 4px; }
  .kop-text p { font-size: 9.5pt; color: #444; line-height: 1.4; }
  .judul { text-align: center; margin: 18px 0 10px; }
  .judul h2 { font-size: 14pt; text-decoration: underline; letter-spacing: 1px; }
  .judul .nomor { font-size: 10.5pt; margin-top: 4px; }
  .tgl-right { text-align: right; font-size: 11pt; margin-bottom: 14px; }
  .pembuka { margin-bottom: 14px; font-size: 11.5pt; line-height: 1.7; }
  table.detail { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 11.5pt; }
  table.detail td { padding: 5px 10px; vertical-align: top; }
  table.detail td:first-child { width: 38%; font-weight: 600; }
  table.detail td:nth-child(2) { width: 4%; }
  .keterangan-box { border: 1px solid #aaa; border-radius: 4px; padding: 10px 14px; margin-bottom: 18px; font-size: 11.5pt; min-height: 48px; font-style: italic; color: #333; }
  .ttd-section { display: flex; justify-content: flex-end; margin-top: 20px; gap: 60px; align-items: flex-start; }
  .ttd-block { text-align: center; min-width: 180px; }
  .ttd-block .ttd-title { font-size: 11pt; font-weight: 600; margin-bottom: 6px; }
  .ttd-block .qr-wrap { border: 1px solid #ccc; border-radius: 8px; padding: 8px; display: inline-block; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .ttd-block .qr-wrap img { display: block; width: 130px; height: 130px; }
  .ttd-block .ttd-code { font-family: monospace; font-size: 8.5pt; color: #333; margin-top: 6px; font-weight: bold; word-break: break-all; }
  .ttd-block .ttd-name { margin-top: 6px; font-size: 10.5pt; border-top: 1px solid #555; padding-top: 4px; min-width: 160px; }
  .footer-note { margin-top: 28px; font-size: 9pt; color: #666; border-top: 1px solid #ddd; padding-top: 8px; display: flex; justify-content: space-between; align-items: center; }
  @media print {
    body { padding: 0; }
    @page { size: A4 portrait; margin: 15mm 20mm; }
  }
</style>
</head>
<body>

<div class="kop">
  <img src="${logoSrc}" class="kop-logo" alt="Logo RS Al-Huda" />
  <div class="kop-text">
    <h1>RUMAH SAKIT AL-HUDA</h1>
    <p>Sistem Informasi Manajemen Perbaikan &amp; Kerusakan Perangkat (SIM-Perbaikan)</p>
    <p>Jl. Raya Gambiran No. 225, Gambiran, Kab. Banyuwangi, Jawa Timur 68486</p>
    <p>Telp: (0333) 842034 / 842038 | Email: rs_alhuda@yahoo.com | Web: www.rsalhuda.co.id</p>
  </div>
</div>

<div class="judul">
  <h2>SURAT PENGANTAR KERUSAKAN BARANG</h2>
  <div class="nomor">Nomor: ${esc(nomor) || '—'}</div>
</div>

<div class="tgl-right">Tanggal: ${esc(tglFmt)}</div>

<div class="pembuka">
  Yang bertanda tangan di bawah ini menyatakan bahwa barang/perangkat berikut telah mengalami kerusakan
  dan perlu ditangani/dikirim ke unit terkait untuk perbaikan lebih lanjut.
</div>

<table class="detail">
  <tr><td>ID Laporan</td><td>:</td><td>#${esc(id)}</td></tr>
  <tr><td>Jenis Barang</td><td>:</td><td>${esc(row.barang)}</td></tr>
  <tr><td>Unit / Ruangan</td><td>:</td><td>${esc(formatUnitLocation(row))}</td></tr>
  <tr><td>No. Seri</td><td>:</td><td>${esc(row.serial_number) || '—'}</td></tr>
  <tr><td>Tanggal Laporan</td><td>:</td><td>${esc(formatTglIndo(row.tanggal))}</td></tr>
  <tr><td>Status Penanganan</td><td>:</td><td>${esc(row.status_penanganan)}</td></tr>
  <tr><td>Rincian Kerusakan</td><td>:</td><td>${esc(row.kerusakan)}</td></tr>
  <tr><td>Uraian Kegiatan</td><td>:</td><td>${esc(row.uraian)}</td></tr>
</table>

<div style="font-weight:600; margin-bottom:6px; font-size:11.5pt;">Keterangan Tambahan:</div>
<div class="keterangan-box">${esc(keterangan) || '(tidak ada keterangan tambahan)'}</div>

<div class="ttd-section">
  <div class="ttd-block">
    <div class="ttd-title">Pelapor / TTE</div>
    <div class="qr-wrap">
      <img src="${qrImg}" alt="QR Code TTD" />
    </div>
    <div class="ttd-code">${esc(ttdCode)}</div>
    <div class="ttd-name"><strong>${esc(namaPelapor)}</strong><br><small>${esc(jabatan)}</small></div>
  </div>
  <div class="ttd-block">
    <div class="ttd-title">Mengetahui,</div>
    <div style="height: 120px; border: 1px dashed #ccc; border-radius:4px; margin-bottom:4px; display:flex; align-items:center; justify-content:center; color:#999; font-size:9pt; font-style:italic;">Stempel &amp; Paraf</div>
    <div class="ttd-name">___________________<br><small>Kepala Unit / Pejabat</small></div>
  </div>
</div>

<div class="footer-note">
  <span>★ Dokumen ini diterbitkan secara digital oleh SIM-Perbaikan RS Al-Huda. Scan QR Code untuk verifikasi keabsahan.</span>
  <span>Kode: <strong>${esc(ttdCode)}</strong></span>
</div>

</body>
</html>`;
}

function formatTglIndo(tgl) {
    if (!tgl) return '—';
    const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const d = new Date(tgl);
    if (isNaN(d.getTime())) return tgl;
    return d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
}

// ──────────────────────────────────────────────────────────────
// Barcode Code 128 — render ke <canvas>
// Implementasi minimal: hanya subset ASCII printable (Code 128B)
// ──────────────────────────────────────────────────────────────
function drawBarcode128(canvas, text) {
    const ctx = canvas.getContext('2d');
    const W = canvas.width;
    const H = canvas.height;
    ctx.clearRect(0, 0, W, H);

    // Code 128B patterns (nilai 0–106)
    // Setiap entry = 11 bit bar/space: 1=bar, 0=space
    const CODE128B_PATTERNS = [
        [2,1,2,2,2,2],[2,2,2,1,2,2],[2,2,2,2,2,1],[1,2,1,2,2,3],[1,2,1,3,2,2],
        [1,3,1,2,2,2],[1,2,2,2,1,3],[1,2,2,3,1,2],[1,3,2,2,1,2],[2,2,1,2,1,3],
        [2,2,1,3,1,2],[2,3,1,2,1,2],[1,1,2,2,3,2],[1,2,2,1,3,2],[1,2,2,2,3,1],
        [1,1,3,2,2,2],[1,2,3,1,2,2],[1,2,3,2,2,1],[2,2,3,2,1,1],[2,2,1,1,3,2],
        [2,2,1,2,3,1],[2,1,3,2,1,2],[2,2,3,1,1,2],[3,1,2,1,3,1],[3,1,1,2,2,2],
        [3,2,1,1,2,2],[3,2,1,2,2,1],[3,1,2,2,1,2],[3,2,2,1,1,2],[3,2,2,2,1,1],
        [2,1,2,1,2,3],[2,1,2,3,2,1],[2,3,2,1,2,1],[1,1,1,3,2,3],[1,3,1,1,2,3],
        [1,3,1,3,2,1],[1,1,2,3,1,3],[1,3,2,1,1,3],[1,3,2,3,1,1],[2,1,1,3,1,3],
        [2,3,1,1,1,3],[2,3,1,3,1,1],[1,1,2,1,3,3],[1,1,2,3,3,1],[1,3,2,1,3,1],
        [1,1,3,1,2,3],[1,1,3,3,2,1],[1,3,3,1,2,1],[3,1,3,1,2,1],[2,1,1,3,3,1],
        [2,3,1,1,3,1],[2,1,3,1,1,3],[2,1,3,3,1,1],[2,1,3,1,3,1],[3,1,1,1,2,3],
        [3,1,1,3,2,1],[3,3,1,1,2,1],[3,1,2,1,1,3],[3,1,2,3,1,1],[3,3,2,1,1,1],
        [3,1,4,1,1,1],[2,2,1,4,1,1],[4,3,1,1,1,1],[1,1,1,2,2,4],[1,1,1,4,2,2],
        [1,2,1,1,2,4],[1,2,1,4,2,1],[1,4,1,1,2,2],[1,4,1,2,2,1],[1,1,2,2,1,4],
        [1,1,2,4,1,2],[1,2,2,1,1,4],[1,2,2,4,1,1],[1,4,2,1,1,2],[1,4,2,2,1,1],
        [2,4,1,2,1,1],[2,2,1,1,1,4],[4,1,3,1,1,1],[2,4,1,1,1,2],[1,3,4,1,1,1],
        [1,1,1,2,4,2],[1,2,1,1,4,2],[1,2,1,2,4,1],[1,1,4,2,1,2],[1,2,4,1,1,2],
        [1,2,4,2,1,1],[4,1,1,2,1,2],[4,2,1,1,1,2],[4,2,1,2,1,1],[2,1,2,1,4,1],
        [2,1,4,1,2,1],[4,1,2,1,2,1],[1,1,1,1,4,3],[1,1,1,3,4,1],[1,3,1,1,4,1],
        [1,1,4,1,1,3],[1,1,4,3,1,1],[4,1,1,1,1,3],[4,1,1,3,1,1],[1,1,3,1,4,1],
        [1,1,4,1,3,1],[3,1,1,1,4,1],[4,1,1,1,3,1],[2,1,1,4,1,2],[2,1,1,2,1,4],
        [2,1,1,2,3,2],[2,3,3,1,1,1],[1,1,2,1,1,4]
    ];

    const START_B = 104;
    const STOP    = 106;

    // Encode chars
    const chars = text.split('').map(c => c.charCodeAt(0) - 32);
    let checksum = START_B;
    chars.forEach((v, i) => { checksum += v * (i + 1); });
    checksum = checksum % 103;

    const allCodes = [START_B, ...chars, checksum, STOP];

    // Calculate total modules
    let totalModules = allCodes.reduce((s, code) => {
        const p = CODE128B_PATTERNS[code] || CODE128B_PATTERNS[0];
        return s + p.reduce((a,b) => a+b, 0);
    }, 0) + 2; // 2 quiet zones

    const moduleW = W / (totalModules + 4);

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, W, H);

    let x = moduleW * 2; // quiet zone start
    allCodes.forEach((code, ci) => {
        const p = CODE128B_PATTERNS[code] || CODE128B_PATTERNS[0];
        p.forEach((width, idx) => {
            const isBar = idx % 2 === 0;
            const barW = width * moduleW;
            if (isBar) {
                ctx.fillStyle = '#111';
                ctx.fillRect(Math.round(x), 0, Math.round(barW), H);
            }
            x += barW;
        });
    });
}