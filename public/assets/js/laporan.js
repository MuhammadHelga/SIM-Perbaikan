document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        ['periode', 'filterStatus'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', () => filterForm.requestSubmit());
        });
    }

    const btnCetak = document.getElementById('btnCetak');
    if (btnCetak) {
        btnCetak.addEventListener('click', function () {
            window.print();
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
});

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
            window.location.href = (window.BASE_URL || '') + '/laporan/hapus/' + id;
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
            window.location.href = (window.BASE_URL || '') + '/laporan/terima/' + id;
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
            window.location.href = (window.BASE_URL || '') + '/laporan/kirim/' + id;
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