/**
 * Modal surat kerusakan + cetak (TTE) untuk halaman laporan.
 * Pratinjau (iframe) dirender dari buildSuratHtml() yang sama dengan hasil cetak.
 */
document.addEventListener('DOMContentLoaded', function () {
    initCustomSelect('suratNamaPelapor', { forceSearch: true });
});

// ============================================================
// SURAT KERUSAKAN MODAL
// ============================================================

let _suratCurrentId = null;
let _suratIsReprint = false;
let _suratQrPelapor = '';
let _suratQrKabag = '';

/**
 * Buat string teks TTE multi-baris standar RS Al-Huda
 * Contoh hasil scan HP:
 *   Dikeluarkan di RUMAH SAKIT AL- HUDA,
 *   Kabupaten/Kota BANYUWANGI
 *   Ditandatangani secara elektronik oleh Dovin Wulandari
 *   Petugas Laboratorium
 */
function buildTteQrText(nama, jabatan) {
    const instansi = window.SURAT_INSTANSI || {};
    const namaRs = instansi.nama || 'RUMAH SAKIT AL- HUDA';
    const kotaRs = instansi.kota || 'Kabupaten/Kota BANYUWANGI';
    const cleanNama = (nama || 'Petugas').trim();
    const cleanJabatan = (jabatan || 'Petugas Unit IT').trim();

    return [
        `Dikeluarkan di ${namaRs},`,
        kotaRs,
        `Ditandatangani secara elektronik oleh`,
        `Nama: ${cleanNama}`,
        `Jabatan: ${cleanJabatan}`
    ].join('\n');
}

function refreshSuratQrs(callback) {
    const id = _suratCurrentId;
    const row = findLaporanById(id);
    if (!row) {
        if (callback) callback();
        return;
    }

    const namaPelapor = (document.getElementById('suratNamaPelapor') ? document.getElementById('suratNamaPelapor').value.trim() : '') || (row.nama_pelapor || '');
    const jabatanPelapor = (document.getElementById('suratJabatan') ? document.getElementById('suratJabatan').value.trim() : '') || (row.jabatan_pelapor || 'Petugas Unit IT');
    const qrTextPelapor = buildTteQrText(namaPelapor, jabatanPelapor);

    const isAcc = (row.acc_status || '') === 'diacc';
    const signer2 = window.SURAT_SIGNER2 || {};
    const kabagName = isAcc ? (row.nama_kabag || signer2.nama || '') : '';
    const kabagJabatan = isAcc ? (row.jabatan_kabag || signer2.jabatan || 'Kabag. Fasilitas dan Keselamatan') : '';
    const qrTextKabag = isAcc && kabagName ? buildTteQrText(kabagName, kabagJabatan) : '';

    const host = window.location.origin;
    const logoUrl = `${host}${window.BASE_URL || ''}/assets/images/logo_alhuda_kop.png`;
    const qrCanvas = document.getElementById('suratQrCanvas');

    if (qrCanvas && typeof window.drawQrWithLogo === 'function') {
        window.drawQrWithLogo(qrCanvas, qrTextPelapor, logoUrl, function (pDataUrl) {
            _suratQrPelapor = pDataUrl || qrCanvas.toDataURL('image/png');
            if (isAcc && qrTextKabag) {
                window.drawQrWithLogo(qrCanvas, qrTextKabag, logoUrl, function (kDataUrl) {
                    _suratQrKabag = kDataUrl || qrCanvas.toDataURL('image/png');
                    renderSuratPreview();
                    if (callback) callback();
                });
            } else {
                _suratQrKabag = '';
                renderSuratPreview();
                if (callback) callback();
            }
        });
    } else {
        renderSuratPreview();
        if (callback) callback();
    }
}

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
    // Label tombol tutup footer: "Batal" (form) / "Tutup" (pratinjau).
    setText('suratFooterCloseLabel', isPreview ? 'Tutup' : 'Batal');

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

    const bulanRomawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    const bln = bulanRomawi[today.getMonth()];
    const thn = today.getFullYear();
    const nomorVal = row.nomor_surat || `${String(id).padStart(3, '0')}/IT/${bln}/${thn}`;
    document.getElementById('suratNomor').value = nomorVal;

    // Pelapor: pilih dari dropdown user terdaftar; jabatan terkunci mengikuti data user
    const namaVal = (row.nama_pelapor && String(row.nama_pelapor).trim() !== '') ? row.nama_pelapor : '';
    let jabVal = row.jabatan_pelapor || '';

    const pelaporSelect = document.getElementById('suratNamaPelapor');
    if (pelaporSelect) {
        // Buang opsi "(tidak terdaftar)" sisa laporan sebelumnya agar tidak menumpuk.
        Array.from(pelaporSelect.options).forEach(function (o) {
            if (o.dataset.temp === '1') o.remove();
        });

        let matched = false;
        for (const opt of pelaporSelect.options) {
            if (opt.value !== '' && opt.value === namaVal) {
                pelaporSelect.value = opt.value;
                matched = true;
                break;
            }
        }
        // Nama tersimpan tapi sudah tidak ada di daftar user (mis. user dihapus):
        // sisipkan opsi sementara agar nilai lama tetap tampil saat reprint.
        if (!matched && namaVal !== '') {
            const tmp = document.createElement('option');
            tmp.value = namaVal;
            tmp.textContent = namaVal + ' (tidak terdaftar)';
            tmp.setAttribute('data-jabatan', jabVal);
            tmp.setAttribute('data-id', '-');
            tmp.dataset.temp = '1';
            pelaporSelect.appendChild(tmp);
            pelaporSelect.value = namaVal;
            matched = true;
        }
        if (!matched) pelaporSelect.value = '';

        const opt = pelaporSelect.selectedOptions && pelaporSelect.selectedOptions[0];
        const jabFromUser = (opt && opt.getAttribute('data-jabatan')) || '';
        document.getElementById('suratJabatan').value = jabFromUser || jabVal;
        jabVal = document.getElementById('suratJabatan').value;

        // Sinkronkan tampilan custom searchable select dengan nilai terbaru.
        refreshCustomSelect('suratNamaPelapor');
    } else {
        document.getElementById('suratJabatan').value = jabVal;
    }
    document.getElementById('suratKeterangan').value = row.keterangan_tambahan || '';

    // Render QR Code TTE standar RS Al-Huda (Opsi 1: Plain Text)
    _suratQrPelapor = '';
    _suratQrKabag = '';
    renderSuratPreview();
    refreshSuratQrs();

    // Sesuaikan header modal & tampilan section
    setSuratMode(shouldShowPreview ? 'preview' : 'form');

    openModal('modal-surat-kerusakan');
}

/**
 * Render pratinjau surat ke iframe memakai buildSuratHtml() — sumber yang SAMA
 * dengan cetak, sehingga pratinjau & hasil cetak selalu identik.
 */
function renderSuratPreview() {
    const frame = document.getElementById('suratPreviewFrame');
    if (!frame) return;

    // Sekali pasang: tinggi iframe mengikuti isi surat agar TIDAK ada scroll
    // di dalam iframe (scroll tunggal ditangani oleh .surat-modal-body).
    if (!frame.dataset.autosize) {
        frame.dataset.autosize = '1';
        frame.addEventListener('load', function () {
            let doc;
            try { doc = frame.contentDocument; } catch (e) { return; }
            if (!doc || !doc.body) return;

            const fit = function () {
                frame.style.height = (doc.documentElement.scrollHeight + 2) + 'px';
            };
            fit();

            // Isi bertambah setelah gambar (logo) selesai dimuat.
            const logo = doc.querySelector('.kop-logo');
            if (logo && !logo.complete) logo.addEventListener('load', fit);

            if (window.ResizeObserver) {
                if (frame._ro) frame._ro.disconnect();
                frame._ro = new ResizeObserver(fit);
                frame._ro.observe(doc.body);
            }
            setTimeout(fit, 200);
        });
    }

    const id = _suratCurrentId;
    const row = findLaporanById(id);
    if (!row) return;

    const fieldValue = (elemId) => {
        const el = document.getElementById(elemId);
        return el ? el.value.trim() : '';
    };

    const nomor = fieldValue('suratNomor');
    const tglSurat = document.getElementById('suratTglSurat') ? document.getElementById('suratTglSurat').value : '';
    const namaPelapor = fieldValue('suratNamaPelapor') || (row.nama_pelapor || '');
    const jabatan = fieldValue('suratJabatan') || (row.jabatan_pelapor || '');
    const keterangan = fieldValue('suratKeterangan');
    const canvas = document.getElementById('suratQrCanvas');
    const qrImg = _suratQrPelapor || (canvas ? canvas.toDataURL('image/png') : '');
    const qrImgKabag = _suratQrKabag;

    frame.srcdoc = buildSuratHtml({
        nomor, tglFmt: formatTglIndo(tglSurat), namaPelapor, jabatan, keterangan, qrImg, qrImgKabag, row, id
    });
}

function closeSuratModal() {
    closeModal('modal-surat-kerusakan');
    _suratCurrentId = null;
    _suratIsReprint = false;
    _suratQrPelapor = '';
    _suratQrKabag = '';

    // Reset pilihan pelapor ke placeholder + sinkronkan custom select.
    const pelaporSelect = document.getElementById('suratNamaPelapor');
    if (pelaporSelect) {
        pelaporSelect.value = '';
        refreshCustomSelect('suratNamaPelapor');
    }
    const jabatanInput = document.getElementById('suratJabatan');
    if (jabatanInput) jabatanInput.value = '';
}

function onSuratApprovalChange() {
    const checked = document.getElementById('suratApprovalCheck').checked;
    const btnCetak = document.getElementById('suratBtnCetak');
    if (btnCetak) btnCetak.disabled = !checked;
}

/**
 * Saat pelapor dipilih dari dropdown: jabatan terkunci mengikuti data user.
 */
function onSuratPelaporChange() {
    const select = document.getElementById('suratNamaPelapor');
    const jabatanInput = document.getElementById('suratJabatan');
    if (!select || !jabatanInput) return;

    const opt = select.selectedOptions && select.selectedOptions[0];
    jabatanInput.value = (opt && opt.getAttribute('data-jabatan')) || '';

    // Re-generate QR Code pelapor mengikuti nama pelapor yang baru dipilih
    refreshSuratQrs();
}

/**
 * Konfirmasi & ACC surat oleh Kabag: aktifkan TTD + QR Kabag pada surat.
 */
function accSurat(id) {
    const row = findLaporanById(id);
    const nama = row ? (row.nama_pelapor || '') : '';
    const jabatanKabag = (window.SURAT_SIGNER2 || {}).jabatan || 'Kabag. Fasilitas dan Keselamatan';
    openConfirmModal({
        icon: 'task_alt',
        title: 'ACC Surat Kerusakan',
        text: `Setujui surat pengantar kerusakan${nama ? ' milik ' + nama : ''}? Setelah di-ACC, surat akan menampilkan tanda tangan dan QR Code ${jabatanKabag}.`,
        confirmLabel: 'ACC Sekarang',
        variant: 'green',
        onConfirm: () => {
            postAction((window.BASE_URL || '') + '/laporan/acc/' + id);
        }
    });
}



/**
 * Cetak surat kerusakan:
 * 1. Validasi form
 * 2. Bangun HTML surat dengan QR Code Berlogo
 * 3. Buka window print baru
 * 4. Kirim POST /laporan/surat/{id} untuk simpan & update status
 */
function cetakSurat() {
    let namaPelapor = document.getElementById('suratNamaPelapor') ? document.getElementById('suratNamaPelapor').value.trim() : '';
    let jabatan = document.getElementById('suratJabatan') ? document.getElementById('suratJabatan').value.trim() : '';
    let tglSurat = document.getElementById('suratTglSurat') ? document.getElementById('suratTglSurat').value : '';
    const nomor = document.getElementById('suratNomor') ? document.getElementById('suratNomor').value.trim() : '';
    const keterangan = document.getElementById('suratKeterangan') ? document.getElementById('suratKeterangan').value.trim() : '';
    const isReprint = _suratIsReprint;

    const suratRow = findLaporanById(_suratCurrentId);
    if (!namaPelapor && suratRow) namaPelapor = suratRow.nama_pelapor || '';
    if (!jabatan && suratRow) jabatan = suratRow.jabatan_pelapor || '';

    if (!namaPelapor) { alert('Nama Pelapor wajib diisi.'); if (document.getElementById('suratNamaPelapor')) document.getElementById('suratNamaPelapor').focus(); return; }
    if (!jabatan) { alert('Jabatan/Bagian wajib diisi.'); if (document.getElementById('suratJabatan')) document.getElementById('suratJabatan').focus(); return; }
    if (!tglSurat) { alert('Tanggal Surat wajib diisi.'); if (document.getElementById('suratTglSurat')) document.getElementById('suratTglSurat').focus(); return; }
    namaPelapor = namaPelapor || 'Petugas Unit IT';
    jabatan = jabatan || 'Penanggung Jawab / Staf IT';
    tglSurat = tglSurat || new Date().toISOString().slice(0, 10);

    const id = _suratCurrentId;
    const row = findLaporanById(id);
    if (!row) return;

    const tglFmt = formatTglIndo(tglSurat);

    const canvas = document.getElementById('suratQrCanvas');
    const qrImg = _suratQrPelapor || (canvas ? canvas.toDataURL('image/png') : '');

    const html = buildSuratHtml({
        nomor, tglFmt, namaPelapor, jabatan, keterangan, qrImg, qrImgKabag: _suratQrKabag, row, id
    });

    const pw = window.open('', '_blank', 'width=900,height=700');
    if (!pw) { alert('Pop-up diblokir browser. Izinkan pop-up untuk mencetak.'); return; }
    pw.document.write(html);
    pw.document.close();
    pw.onload = function () {
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

function buildSuratHtml({ nomor, tglFmt, namaPelapor, jabatan, keterangan, qrImg, qrImgKabag, row, id }) {
    const esc = (s) => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    const logoSrc = `${window.location.origin}${window.BASE_URL || ''}/assets/images/logo_alhuda_kop.png`;

    // Penandatangan kedua — label/jabatan dari config/pengaturan.php (fallback);
    // nama mengikuti user Kabag yang meng-ACC surat.
    const signer2 = window.SURAT_SIGNER2 || {};
    const signer2Label = signer2.label || '';
    const signer2Jabatan = signer2.jabatan || '';
    const signer2Nama = signer2.nama || '';

    // Kolom kanan (Kabag): QR + nama hanya bila surat sudah di-ACC.
    const isAcc = (row.acc_status || '') === 'diacc';
    const kabagJabatan = isAcc ? (row.jabatan_kabag || signer2Jabatan) : signer2Jabatan;
    const kabagName = isAcc ? (row.nama_kabag || signer2Nama) : '';

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
  .ttd-section { display: flex; justify-content: space-between; margin-top: 20px; gap: 60px; align-items: flex-start; }
  .ttd-block { text-align: center; min-width: 180px; }
  .ttd-block .ttd-date { font-size: 11pt; margin-bottom: 6px; text-align: start; }
  .ttd-block .ttd-date-placeholder { font-size: 11pt; margin-bottom: 6px; visibility: hidden; text-align: start; }
  .ttd-block .ttd-title { font-size: 11pt; font-weight: 600; margin-bottom: 4px; text-align: start; }
  .ttd-block .ttd-jabatan { font-size: 10pt; color: #222; margin-bottom: 8px; min-height: 20px; text-align: start; }
  .ttd-block .qr-wrap { border: 1px solid #ccc; border-radius: 8px; padding: 6px; display: inline-block; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .ttd-block .qr-wrap img { display: block; width: 115px; height: 115px; }
  .ttd-block .ttd-name { margin-top: 8px; font-size: 10.5pt; border-top: 1px solid #555; padding-top: 4px; min-width: 160px; font-weight: bold; }
  .footer-note { margin-top: 28px; font-size: 9pt; color: #666; border-top: 1px solid #ddd; padding-top: 8px; }
  /* Pratinjau di layar (iframe): padding lebih kecil agar tidak terlalu wrap. */
  @media screen {
    body { background: #fff; padding: 14px 18px; }
    .ttd-block .qr-wrap img { width: 100px; height: 100px; }
  }
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

<div class="pembuka">
  Yang bertanda tangan di bawah ini menyatakan bahwa barang/perangkat berikut telah mengalami kerusakan
  dan perlu ditangani/dikirim ke unit terkait untuk perbaikan lebih lanjut.
</div>

<table class="detail">
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
    <div class="ttd-date-placeholder">&nbsp;</div>
    <div class="ttd-title">Mengetahui,</div>
    <div class="ttd-jabatan">${esc(jabatan)}</div>
    <div class="qr-wrap">
      <img src="${qrImg}" alt="QR Code TTD Petugas IT" />
    </div>
    <div class="ttd-name">${esc(namaPelapor)}</div>
  </div>
  <div class="ttd-block">
    <div class="ttd-date">Banyuwangi, ${esc(tglFmt)}</div>
    <div class="ttd-title">${esc(signer2Label)}</div>
    <div class="ttd-jabatan">${esc(kabagJabatan)}</div>
    ${isAcc && qrImgKabag
            ? `<div class="qr-wrap"><img src="${qrImgKabag}" alt="QR Code TTD Kabag" /></div>`
            : `<div style="font-style:italic; color:#b45309; font-size:10pt; padding:12px 4px; min-height:115px; display:flex; align-items:center; justify-content:center; text-align:center;">Menunggu persetujuan ${esc(signer2Jabatan)}</div>`}
    ${kabagName ? `<div class="ttd-name">${esc(kabagName)}</div>` : ''}
  </div>
</div>

<div class="footer-note">
  <span>*Dokumen ini diterbitkan secara digital oleh SIM-Perbaikan RS Al-Huda. Scan QR Code untuk verifikasi keabsahan.</span>
</div>

</body>
</html>`;
}

function formatTglIndo(tgl) {
    if (!tgl) return '—';
    const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
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
        [2, 1, 2, 2, 2, 2], [2, 2, 2, 1, 2, 2], [2, 2, 2, 2, 2, 1], [1, 2, 1, 2, 2, 3], [1, 2, 1, 3, 2, 2],
        [1, 3, 1, 2, 2, 2], [1, 2, 2, 2, 1, 3], [1, 2, 2, 3, 1, 2], [1, 3, 2, 2, 1, 2], [2, 2, 1, 2, 1, 3],
        [2, 2, 1, 3, 1, 2], [2, 3, 1, 2, 1, 2], [1, 1, 2, 2, 3, 2], [1, 2, 2, 1, 3, 2], [1, 2, 2, 2, 3, 1],
        [1, 1, 3, 2, 2, 2], [1, 2, 3, 1, 2, 2], [1, 2, 3, 2, 2, 1], [2, 2, 3, 2, 1, 1], [2, 2, 1, 1, 3, 2],
        [2, 2, 1, 2, 3, 1], [2, 1, 3, 2, 1, 2], [2, 2, 3, 1, 1, 2], [3, 1, 2, 1, 3, 1], [3, 1, 1, 2, 2, 2],
        [3, 2, 1, 1, 2, 2], [3, 2, 1, 2, 2, 1], [3, 1, 2, 2, 1, 2], [3, 2, 2, 1, 1, 2], [3, 2, 2, 2, 1, 1],
        [2, 1, 2, 1, 2, 3], [2, 1, 2, 3, 2, 1], [2, 3, 2, 1, 2, 1], [1, 1, 1, 3, 2, 3], [1, 3, 1, 1, 2, 3],
        [1, 3, 1, 3, 2, 1], [1, 1, 2, 3, 1, 3], [1, 3, 2, 1, 1, 3], [1, 3, 2, 3, 1, 1], [2, 1, 1, 3, 1, 3],
        [2, 3, 1, 1, 1, 3], [2, 3, 1, 3, 1, 1], [1, 1, 2, 1, 3, 3], [1, 1, 2, 3, 3, 1], [1, 3, 2, 1, 3, 1],
        [1, 1, 3, 1, 2, 3], [1, 1, 3, 3, 2, 1], [1, 3, 3, 1, 2, 1], [3, 1, 3, 1, 2, 1], [2, 1, 1, 3, 3, 1],
        [2, 3, 1, 1, 3, 1], [2, 1, 3, 1, 1, 3], [2, 1, 3, 3, 1, 1], [2, 1, 3, 1, 3, 1], [3, 1, 1, 1, 2, 3],
        [3, 1, 1, 3, 2, 1], [3, 3, 1, 1, 2, 1], [3, 1, 2, 1, 1, 3], [3, 1, 2, 3, 1, 1], [3, 3, 2, 1, 1, 1],
        [3, 1, 4, 1, 1, 1], [2, 2, 1, 4, 1, 1], [4, 3, 1, 1, 1, 1], [1, 1, 1, 2, 2, 4], [1, 1, 1, 4, 2, 2],
        [1, 2, 1, 1, 2, 4], [1, 2, 1, 4, 2, 1], [1, 4, 1, 1, 2, 2], [1, 4, 1, 2, 2, 1], [1, 1, 2, 2, 1, 4],
        [1, 1, 2, 4, 1, 2], [1, 2, 2, 1, 1, 4], [1, 2, 2, 4, 1, 1], [1, 4, 2, 1, 1, 2], [1, 4, 2, 2, 1, 1],
        [2, 4, 1, 2, 1, 1], [2, 2, 1, 1, 1, 4], [4, 1, 3, 1, 1, 1], [2, 4, 1, 1, 1, 2], [1, 3, 4, 1, 1, 1],
        [1, 1, 1, 2, 4, 2], [1, 2, 1, 1, 4, 2], [1, 2, 1, 2, 4, 1], [1, 1, 4, 2, 1, 2], [1, 2, 4, 1, 1, 2],
        [1, 2, 4, 2, 1, 1], [4, 1, 1, 2, 1, 2], [4, 2, 1, 1, 1, 2], [4, 2, 1, 2, 1, 1], [2, 1, 2, 1, 4, 1],
        [2, 1, 4, 1, 2, 1], [4, 1, 2, 1, 2, 1], [1, 1, 1, 1, 4, 3], [1, 1, 1, 3, 4, 1], [1, 3, 1, 1, 4, 1],
        [1, 1, 4, 1, 1, 3], [1, 1, 4, 3, 1, 1], [4, 1, 1, 1, 1, 3], [4, 1, 1, 3, 1, 1], [1, 1, 3, 1, 4, 1],
        [1, 1, 4, 1, 3, 1], [3, 1, 1, 1, 4, 1], [4, 1, 1, 1, 3, 1], [2, 1, 1, 4, 1, 2], [2, 1, 1, 2, 1, 4],
        [2, 1, 1, 2, 3, 2], [2, 3, 3, 1, 1, 1], [1, 1, 2, 1, 1, 4]
    ];

    const START_B = 104;
    const STOP = 106;

    // Encode chars
    const chars = text.split('').map(c => c.charCodeAt(0) - 32);
    let checksum = START_B;
    chars.forEach((v, i) => { checksum += v * (i + 1); });
    checksum = checksum % 103;

    const allCodes = [START_B, ...chars, checksum, STOP];

    // Calculate total modules
    let totalModules = allCodes.reduce((s, code) => {
        const p = CODE128B_PATTERNS[code] || CODE128B_PATTERNS[0];
        return s + p.reduce((a, b) => a + b, 0);
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
