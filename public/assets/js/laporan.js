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
            document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
        }
    });

    const unitSelect = document.getElementById('unit');
    if (unitSelect) {
        unitSelect.addEventListener('change', updateJenisPoliVisibility);
    }
    const incidentTime = document.getElementById('waktu_kejadian');
    if (incidentTime) {
        incidentTime.addEventListener('change', function () {
            if (incidentTime.value) {
                document.getElementById('tanggal').value = incidentTime.value.slice(0, 10);
            }
        });
    }

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

function openTambahModal() {
    setFormMode('create');
    const form = document.getElementById('form-tambah-laporan');
    clearLaporanHistory();
    clearLegacyJenisPoliOption();
    form.reset();
    updateJenisPoliVisibility();
    document.getElementById('form-laporan-id').value = '';
    document.getElementById('waktu_kejadian').value = getCurrentLocalDateTime();
    setRecordedAt(null);
    document.getElementById('prioritas').value = 'Sedang';
    form.action = (window.BASE_URL || '') + '/laporan/simpan';

    document.getElementById('formLaporanIcon').textContent = 'note_add';
    document.getElementById('formLaporanTitle').textContent = 'Tambah Laporan Baru';
    document.getElementById('formLaporanDesc').textContent = 'Input catatan kerusakan perangkat fasilitas rumah sakit';

    openModal('modal-form-laporan');
}

function openEditModal(id) {
    const row = findLaporanById(id);
    if (!row) { alert('Data laporan tidak ditemukan.'); return; }

    setFormMode('edit');
    clearLaporanHistory();
    fillFormWithData(row);
    setRecordedAt(null);

    const form = document.getElementById('form-tambah-laporan');
    form.action = (window.BASE_URL || '') + '/laporan/update';

    document.getElementById('formLaporanIcon').textContent = 'edit';
    document.getElementById('formLaporanTitle').textContent = 'Edit Laporan';
    document.getElementById('formLaporanDesc').textContent = 'Perbarui data laporan #' + id;

    openModal('modal-form-laporan');
}

function openDetailModal(id) {
    const row = findLaporanById(id);
    if (!row) { alert('Data laporan tidak ditemukan.'); return; }

    fillFormWithData(row);
    setFormMode('view');
    setRecordedAt(row.created_at || null);

    document.getElementById('formLaporanIcon').textContent = 'visibility';
    document.getElementById('formLaporanTitle').textContent = 'Detail Laporan';
    document.getElementById('formLaporanDesc').textContent = 'Laporan #' + id + ' (hanya lihat)';

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
    selectOptionByText('jenis_barang', row.barang);

    document.getElementById('no_seri').value = (row.serial_number && row.serial_number !== '-') ? row.serial_number : '';
    document.getElementById('rincian_kerusakan').value = row.kerusakan || '';
    document.getElementById('uraian_kegiatan').value = row.uraian || '';

    const statusValue = row.status_penanganan
        || (row.hasil === 'selesai' ? 'Selesai' : 'Pending');
    document.getElementById('status').value = statusValue;

    document.getElementById('prioritas').value = row.prioritas || 'Sedang';
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

function toDateInputValue(rawDate) {
    if (!rawDate) return '';
    const d = new Date(rawDate);
    if (isNaN(d.getTime())) return '';
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

function setFormMode(mode) {
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
    const headerIcon = document.getElementById('suratModalHeaderIcon');
    const headerTitle = document.getElementById('suratModalHeaderTitle');
    const headerDesc  = document.getElementById('suratModalHeaderDesc');
    const formSection = document.getElementById('suratFormSection');
    const previewSection = document.getElementById('suratDocumentPreviewSection');

    if (shouldShowPreview) {
        if (headerIcon)  headerIcon.textContent  = 'description';
        if (headerTitle) headerTitle.textContent  = 'Surat Kerusakan Resmi (TTE)';
        if (headerDesc)  headerDesc.textContent   = 'Dokumen surat pengantar kerusakan ber-TTE yang siap dicetak';
        document.getElementById('suratReprintBadge').style.display = 'flex';
        document.getElementById('suratReprintBadge').textContent = 'Surat Terbit (TTE)';
        document.getElementById('suratApprovalCheck').checked = true;
        if (document.getElementById('suratBtnCetak')) document.getElementById('suratBtnCetak').disabled = false;
        if (formSection) formSection.style.display = 'none';
        if (previewSection) previewSection.style.display = 'block';
    } else {
        if (headerIcon)  headerIcon.textContent  = 'edit_note';
        if (headerTitle) headerTitle.textContent  = 'Form Surat Kerusakan';
        if (headerDesc)  headerDesc.textContent   = 'Isi form di bawah untuk menerbitkan QR Code TTE dan surat kerusakan';
        document.getElementById('suratReprintBadge').style.display = 'none';
        document.getElementById('suratApprovalCheck').checked = true;
        if (document.getElementById('suratBtnCetak')) document.getElementById('suratBtnCetak').disabled = false;
        if (formSection) formSection.style.display = 'block';
        if (previewSection) previewSection.style.display = 'none';
    }

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

function switchToFormMode() {
    const formSection = document.getElementById('suratFormSection');
    const previewSection = document.getElementById('suratDocumentPreviewSection');
    const headerTitle = document.getElementById('suratModalHeaderTitle');
    const headerDesc = document.getElementById('suratModalHeaderDesc');

    if (formSection) formSection.style.display = 'block';
    if (previewSection) previewSection.style.display = 'none';
    if (headerTitle) headerTitle.textContent = 'Form Surat Kerusakan';
    if (headerDesc) headerDesc.textContent = 'Isi form di bawah sebelum mencetak surat pengantar kerusakan';
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