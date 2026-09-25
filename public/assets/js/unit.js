const UNIT_LABEL = { ruangan: 'Unit/Ruangan', barang: 'Barang' };
const UNIT_ICON  = { ruangan: 'meeting_room', barang: 'inventory_2' };

document.addEventListener('DOMContentLoaded', function () {
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
});

function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

function openUnitModal(jenis) {
    const form = document.getElementById('form-unit');
    form.reset();
    document.getElementById('unit-id').value = '';
    form.action = (window.BASE_URL || '') + '/unit/' + jenis + '/simpan';

    document.getElementById('unitModalIcon').textContent = UNIT_ICON[jenis] || 'add_circle';
    document.getElementById('unitModalTitle').textContent = 'Tambah ' + (UNIT_LABEL[jenis] || 'Data');
    document.getElementById('unitModalDesc').textContent = 'Tambah data ' + (UNIT_LABEL[jenis] || 'data').toLowerCase() + ' baru';

    openModal('modal-unit');
}

function openUnitEdit(btn) {
    const jenis = btn.dataset.jenis;
    const form  = document.getElementById('form-unit');

    document.getElementById('unit-id').value   = btn.dataset.id || '';
    document.getElementById('unit-kode').value = btn.dataset.kode || '';
    document.getElementById('unit-nama').value = btn.dataset.nama || '';
    form.action = (window.BASE_URL || '') + '/unit/' + jenis + '/simpan';

    document.getElementById('unitModalIcon').textContent = 'edit';
    document.getElementById('unitModalTitle').textContent = 'Edit ' + (UNIT_LABEL[jenis] || 'Data');
    document.getElementById('unitModalDesc').textContent = 'Perbarui data ' + (UNIT_LABEL[jenis] || 'data').toLowerCase();

    openModal('modal-unit');
}

function confirmUnitDelete(jenis, id, nama) {
    openConfirmModal({
        icon: 'delete',
        title: 'Hapus ' + (UNIT_LABEL[jenis] || 'Data'),
        text: 'Yakin hapus "' + nama + '"? Data yang masih dipakai laporan tidak akan terhapus.',
        confirmLabel: 'Ya, Hapus',
        variant: 'red',
        onConfirm: function () {
            window.location.href = (window.BASE_URL || '') + '/unit/' + jenis + '/hapus/' + id;
        }
    });
}

function openConfirmModal({ icon, title, text, confirmLabel, variant = 'primary', onConfirm }) {
    const iconEl   = document.getElementById('confirmIcon');
    const iconWrap = iconEl.parentElement;

    iconEl.textContent = icon || 'help';
    document.getElementById('confirmTitle').textContent = title || 'Konfirmasi';
    document.getElementById('confirmText').textContent = text || 'Apakah kamu yakin?';

    iconWrap.classList.remove('confirm-card__icon--green', 'confirm-card__icon--red');
    if (variant === 'green') iconWrap.classList.add('confirm-card__icon--green');
    if (variant === 'red')   iconWrap.classList.add('confirm-card__icon--red');

    const oldBtn = document.getElementById('confirmActionBtn');
    oldBtn.textContent = confirmLabel || 'Ya, Lanjutkan';

    let buttonClass = 'confirm-btn--primary';
    if (variant === 'green') buttonClass = 'confirm-btn--success';
    else if (variant === 'red') buttonClass = 'confirm-btn--danger';
    oldBtn.className = 'confirm-btn ' + buttonClass;

    const freshBtn = oldBtn.cloneNode(true);
    oldBtn.parentNode.replaceChild(freshBtn, oldBtn);
    freshBtn.addEventListener('click', function () {
        closeModal('modal-konfirmasi');
        onConfirm();
    });

    openModal('modal-konfirmasi');
}
