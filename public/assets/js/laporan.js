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
    form.reset();
    document.getElementById('form-laporan-id').value = '';
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
    fillFormWithData(row);

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

    setFormMode('view');
    fillFormWithData(row);

    document.getElementById('formLaporanIcon').textContent = 'visibility';
    document.getElementById('formLaporanTitle').textContent = 'Detail Laporan';
    document.getElementById('formLaporanDesc').textContent = 'Laporan #' + id + ' (hanya lihat)';

    openModal('modal-form-laporan');
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

    selectOptionByText('unit', row.urusan);
    selectOptionByText('jenis_barang', row.barang);

    document.getElementById('no_seri').value = (row.serial_number && row.serial_number !== '-') ? row.serial_number : '';
    document.getElementById('rincian_kerusakan').value = row.kerusakan || '';
    document.getElementById('uraian_kegiatan').value = row.uraian || '';

    const statusValue = row.status_penanganan
        || (row.hasil === 'selesai' ? 'Selesai' : 'Pending');
    document.getElementById('status').value = statusValue;

    document.getElementById('prioritas').value = row.prioritas || 'Sedang';
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

function openSuratModal(id, isReprint = false) {
    const row = findLaporanById(id);
    if (!row) { alert('Data laporan tidak ditemukan.'); return; }

    _suratCurrentId = id;

    // Isi info laporan
    document.getElementById('suratLaporanId').textContent = id;
    document.getElementById('suratInfoDetail').textContent =
        ' — ' + (row.barang || '?') + ' | ' + (row.urusan || '?');

    // Ubah header modal sesuai mode
    const headerIcon = document.getElementById('suratModalHeaderIcon');
    const headerTitle = document.getElementById('suratModalHeaderTitle');
    const headerDesc  = document.getElementById('suratModalHeaderDesc');
    const approvalBox = document.getElementById('suratApprovalBox');

    if (isReprint) {
        if (headerIcon)  headerIcon.textContent  = 'print';
        if (headerTitle) headerTitle.textContent  = 'Lihat & Cetak Ulang Surat';
        if (headerDesc)  headerDesc.textContent   = 'Isi ulang atau langsung cetak surat kerusakan yang sudah dikirim';
        // Tampilkan badge reprint
        document.getElementById('suratReprintBadge').style.display = 'flex';
        // Approval sudah tidak wajib di reprint — checkbox langsung dicentang
        document.getElementById('suratApprovalCheck').checked = true;
        document.getElementById('suratBtnCetak').disabled = false;
    } else {
        if (headerIcon)  headerIcon.textContent  = 'description';
        if (headerTitle) headerTitle.textContent  = 'Form Surat Kerusakan';
        if (headerDesc)  headerDesc.textContent   = 'Isi form di bawah sebelum mencetak surat pengantar kerusakan';
        document.getElementById('suratReprintBadge').style.display = 'none';
        document.getElementById('suratApprovalCheck').checked = false;
        document.getElementById('suratBtnCetak').disabled = true;
    }

    // Default tanggal hari ini
    const today = new Date();
    document.getElementById('suratTglSurat').value = today.toISOString().slice(0, 10);

    // Auto-nomor surat (opsional, bisa diedit)
    const bulanRomawi = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    const bln = bulanRomawi[today.getMonth()];
    const thn = today.getFullYear();
    document.getElementById('suratNomor').value = `${String(id).padStart(3,'0')}/IT/${bln}/${thn}`;

    // Reset field isian (nama/jabatan/keterangan)
    document.getElementById('suratNamaPelapor').value = '';
    document.getElementById('suratJabatan').value = '';
    document.getElementById('suratKeterangan').value = '';

    // Buat kode TTD digital unik
    // Saat reprint, gunakan kode stabil berbasis tgl_kirim agar konsisten
    const ttdCode = isReprint
        ? generateStableTtdCode(id, row)
        : generateTtdCode(id, row);
    document.getElementById('suratTtdCode').textContent = ttdCode;

    // URL verifikasi yang encoded di QR Code (akan dibuka saat QR discan HP)
    const host = window.location.origin;
    const verifyUrl = `${host}${window.BASE_URL || ''}/surat/verifikasi/${id}?code=${encodeURIComponent(ttdCode)}`;
    const logoUrl = `${host}${window.BASE_URL || ''}/assets/images/logo_alhuda.svg`;

    // Render QR Code dengan logo di tengah canvas
    const qrCanvas = document.getElementById('suratQrCanvas');
    if (qrCanvas && typeof window.drawQrWithLogo === 'function') {
        window.drawQrWithLogo(qrCanvas, verifyUrl, logoUrl, function(dataUrl) {
            const pvQrImg = document.getElementById('pvSuratQrImg');
            if (pvQrImg) pvQrImg.src = dataUrl;
        });
    }

    // Update elemen pratinjau dokumen jadi (paper view)
    const tglSuratVal = document.getElementById('suratTglSurat').value;
    const nomorVal    = document.getElementById('suratNomor').value;
    const namaVal     = document.getElementById('suratNamaPelapor').value.trim() || 'Petugas Unit IT';
    const jabVal      = document.getElementById('suratJabatan').value.trim() || 'Penanggung Jawab / Staf IT';
    const ketVal      = document.getElementById('suratKeterangan').value.trim();

    if (document.getElementById('pvSuratNomor')) document.getElementById('pvSuratNomor').textContent = nomorVal || '—';
    if (document.getElementById('pvSuratTgl')) document.getElementById('pvSuratTgl').textContent = formatTglIndo(tglSuratVal);
    if (document.getElementById('pvSuratId')) document.getElementById('pvSuratId').textContent = id;
    if (document.getElementById('pvSuratBarang')) document.getElementById('pvSuratBarang').textContent = row.barang || '—';
    if (document.getElementById('pvSuratRuangan')) document.getElementById('pvSuratRuangan').textContent = row.urusan || '—';
    if (document.getElementById('pvSuratSn')) document.getElementById('pvSuratSn').textContent = row.serial_number || '—';
    if (document.getElementById('pvSuratTglLaporan')) document.getElementById('pvSuratTglLaporan').textContent = formatTglIndo(row.tanggal);
    if (document.getElementById('pvSuratStatus')) document.getElementById('pvSuratStatus').textContent = row.status_penanganan || 'Pending';
    if (document.getElementById('pvSuratRincian')) document.getElementById('pvSuratRincian').textContent = row.kerusakan || '—';
    if (document.getElementById('pvSuratUraian')) document.getElementById('pvSuratUraian').textContent = row.uraian || '—';
    if (document.getElementById('pvSuratKeterangan')) document.getElementById('pvSuratKeterangan').textContent = ketVal || '(tidak ada keterangan tambahan)';
    if (document.getElementById('pvSuratTtdCode')) document.getElementById('pvSuratTtdCode').textContent = ttdCode;
    if (document.getElementById('pvSuratNamaPelapor')) document.getElementById('pvSuratNamaPelapor').textContent = namaVal;
    if (document.getElementById('pvSuratJabatan')) document.getElementById('pvSuratJabatan').textContent = jabVal;

    // Tampilkan tampilan surat jadi jika isReprint (Lihat Surat), atau form jika Kirim baru
    const formSection = document.getElementById('suratFormSection');
    const previewSection = document.getElementById('suratDocumentPreviewSection');

    if (isReprint) {
        if (formSection) formSection.style.display = 'none';
        if (previewSection) previewSection.style.display = 'block';
        if (headerTitle) headerTitle.textContent = 'Pratinjau Surat Kerusakan (Dokumen Jadi)';
        if (headerDesc) headerDesc.textContent = 'Dokumen surat pengantar kerusakan resmi yang sudah siap dicetak';
    } else {
        if (formSection) formSection.style.display = 'block';
        if (previewSection) previewSection.style.display = 'none';
        if (headerTitle) headerTitle.textContent = 'Form Surat Kerusakan';
        if (headerDesc) headerDesc.textContent = 'Isi form di bawah sebelum mencetak surat pengantar kerusakan';
    }

    openModal('modal-surat-kerusakan');
}

function switchToFormMode() {
    const formSection = document.getElementById('suratFormSection');
    const previewSection = document.getElementById('suratDocumentPreviewSection');
    const headerTitle = document.getElementById('suratModalHeaderTitle');
    const headerDesc = document.getElementById('suratModalHeaderDesc');

    if (formSection) formSection.style.display = 'block';
    if (previewSection) previewSection.style.display = 'none';
    if (headerTitle) headerTitle.textContent = 'Form Surat Kerusakan';
    if (headerDesc) headerDesc.textContent = 'Edit isian form di bawah jika ada penyesuaian';
}

function closeSuratModal() {
    closeModal('modal-surat-kerusakan');
    _suratCurrentId = null;
}

function onSuratApprovalChange() {
    const checked = document.getElementById('suratApprovalCheck').checked;
    document.getElementById('suratBtnCetak').disabled = !checked;
}

/**
 * Menghasilkan kode TTD digital unik berdasarkan id laporan + timestamp + serial
 * (digunakan saat pertama kali kirim)
 */
function generateTtdCode(id, row) {
    const ts = Date.now().toString(36).toUpperCase();
    const sn = (row.serial_number || 'SN').replace(/[^A-Z0-9]/gi, '').slice(0, 6).toUpperCase();
    return `LPR${String(id).padStart(4,'0')}-${sn}-${ts}`;
}

/**
 * Kode TTD stabil untuk cetak ulang — berbasis tgl_kirim + id
 * sehingga kode sama setiap kali surat dicetak ulang
 */
function generateStableTtdCode(id, row) {
    const tglKirim = (row.tgl_kirim || '').replace(/-/g, '');
    const sn = (row.serial_number || 'SN').replace(/[^A-Z0-9]/gi, '').slice(0, 6).toUpperCase();
    const seed = (tglKirim + String(id)).split('').reduce((acc, c) => acc + c.charCodeAt(0), 0);
    const seedCode = seed.toString(36).toUpperCase().padStart(4, '0');
    return `LPR${String(id).padStart(4,'0')}-${sn}-${seedCode}R`;
}

/**
 * Cetak surat kerusakan:
 * 1. Validasi form
 * 2. Bangun HTML surat dengan QR Code Berlogo
 * 3. Buka window print baru
 * 4. Kirim POST /laporan/kirim/{id} untuk update status
 */
function cetakSurat() {
    const namaPelapor = document.getElementById('suratNamaPelapor').value.trim();
    const jabatan     = document.getElementById('suratJabatan').value.trim();
    const tglSurat    = document.getElementById('suratTglSurat').value;
    const nomor       = document.getElementById('suratNomor').value.trim();
    const keterangan  = document.getElementById('suratKeterangan').value.trim();
    const ttdCode     = document.getElementById('suratTtdCode').textContent;

    if (!namaPelapor) { alert('Nama Pelapor wajib diisi.'); document.getElementById('suratNamaPelapor').focus(); return; }
    if (!jabatan)     { alert('Jabatan/Bagian wajib diisi.'); document.getElementById('suratJabatan').focus(); return; }
    if (!tglSurat)    { alert('Tanggal Surat wajib diisi.'); document.getElementById('suratTglSurat').focus(); return; }

    const id  = _suratCurrentId;
    const row = findLaporanById(id);
    if (!row) return;

    // Format tanggal
    const tglFmt = formatTglIndo(tglSurat);

    // QR Code sebagai Data URL dari canvas
    const canvas = document.getElementById('suratQrCanvas');
    const qrImg = canvas ? canvas.toDataURL('image/png') : '';

    const html = buildSuratHtml({
        nomor, tglFmt, namaPelapor, jabatan, keterangan, ttdCode, qrImg, row, id
    });

    // Buka window cetak
    const pw = window.open('', '_blank', 'width=900,height=700');
    if (!pw) { alert('Pop-up diblokir browser. Izinkan pop-up untuk mencetak.'); return; }
    pw.document.write(html);
    pw.document.close();
    pw.onload = function() {
        pw.focus();
        pw.print();
    };

    // Tutup modal & update status kirim via POST
    closeSuratModal();
    postAction((window.BASE_URL || '') + '/laporan/kirim/' + id);
}

function buildSuratHtml({ nomor, tglFmt, namaPelapor, jabatan, keterangan, ttdCode, qrImg, row, id }) {
    const esc = (s) => String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const logoSrc = `${window.location.origin}${window.BASE_URL || ''}/assets/images/logo_alhuda.svg`;

    return `<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Kerusakan #${id}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #111; background: #fff; padding: 20mm 25mm; }
  .kop { display: flex; align-items: center; gap: 18px; border-bottom: 3px double #00288e; padding-bottom: 12px; margin-bottom: 16px; }
  .kop-logo { width: 70px; height: 70px; object-fit: contain; }
  .kop-text h1 { font-size: 16pt; color: #00288e; font-weight: bold; letter-spacing: 0.5px; }
  .kop-text p { font-size: 10pt; color: #444; }
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
    <p>Jl. Raya Al-Huda · Telp. (xxx) xxxx-xxxx</p>
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
  <tr><td>Unit / Ruangan</td><td>:</td><td>${esc(row.urusan)}</td></tr>
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
    <div class="ttd-title">Pelapor / TTE Digital</div>
    <div class="qr-wrap">
      <img src="${qrImg}" alt="QR Code TTD Digital" />
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