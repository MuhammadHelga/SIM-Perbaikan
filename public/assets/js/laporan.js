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
    if (confirm('Yakin hapus laporan #' + id + '?')) window.location.href = '/laporan/hapus/' + id;
}

function kirimBarang(id) {
    if (confirm('Tandai barang #' + id + ' sudah dikirim?')) window.location.href = '/laporan/kirim/' + id;
}

function terimaBarang(id) {
    if (confirm('Tandai barang #' + id + ' sudah diterima?')) window.location.href = '/laporan/terima/' + id;
}