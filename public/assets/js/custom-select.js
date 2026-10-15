/**
 * Komponen "custom searchable select" — pengganti tampilan <select> native:
 * dropdown dengan kotak pencarian (opsional) + daftar ber-scroll.
 * <select> aslinya tetap jadi sumber data (value/options), jadi logika lain
 * tidak perlu diubah. Dipakai oleh laporan.js & surat.js.
 */
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
    const forceSearch = Boolean(options.forceSearch);

    const select = document.getElementById(selectId);
    if (!select || select.dataset.csReady === '1') return;
    select.dataset.csReady = '1';

    const wrapper = select.closest('.input-icon-wrapper') || select.parentElement;
    const placeholderOpt = Array.from(select.options).find(o => o.disabled && o.value === '');
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
        // Sembunyikan kotak cari bila opsinya sedikit (muat tanpa scroll/search),
        // kecuali forceSearch aktif (mis. dropdown pelapor surat).
        if (searchWrap) {
            searchWrap.style.display = (forceSearch || list.querySelectorAll('.cs__option').length > 6) ? '' : 'none';
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
        panel.style.left = r.left + 'px';

        const spaceBelow = window.innerHeight - r.bottom - gap;
        const spaceAbove = r.top - gap;
        const openUp = spaceBelow < 300 && spaceAbove > spaceBelow;

        panel.classList.toggle('cs__panel--up', openUp);
        if (openUp) {
            panel.style.top = 'auto';
            panel.style.bottom = (window.innerHeight - r.top + gap) + 'px';
        } else {
            panel.style.bottom = 'auto';
            panel.style.top = (r.bottom + gap) + 'px';
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
    root._csSync = sync;

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
