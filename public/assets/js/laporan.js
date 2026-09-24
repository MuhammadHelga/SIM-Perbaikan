document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        ['periode', 'status'].forEach(function (id) {
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

    // Tutup modal saat klik area gelap di luar form
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.classList.remove('open');
        }
    });

    // Tutup modal saat tekan ESC
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
    const form = document.getElementById('form-tambah-laporan');
    if (form) form.reset();
    openModal('modal-tambah-laporan');
}

function openEditModal(id) {
    alert('Edit laporan #' + id + ' — modal edit belum dipasang.');
}

function confirmDelete(id) {
    if (confirm('Yakin hapus laporan #' + id + '?')) window.location.href = (window.BASE_URL || '') + '/laporan/hapus/' + id;
}

function kirimBarang(id) {
    if (confirm('Tandai barang #' + id + ' sudah dikirim?')) window.location.href = (window.BASE_URL || '') + '/laporan/kirim/' + id;
}

function terimaBarang(id) {
    if (confirm('Tandai barang #' + id + ' sudah diterima?')) window.location.href = (window.BASE_URL || '') + '/laporan/terima/' + id;
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
    let viewYear = selectedYear; // tahun yang sedang ditampilkan di panel (bisa beda dari yang terpilih, saat browsing)

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

    trigger.addEventListener('click', function (e) {
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