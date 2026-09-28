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

function openSubnetModal() {
    const form = document.getElementById('form-subnet');
    form.reset();
    document.getElementById('subnet-id').value = '';
    form.action = (window.BASE_URL || '') + '/subnet/simpan';

    document.getElementById('subnetModalIcon').textContent = 'account_tree';
    document.getElementById('subnetModalTitle').textContent = 'Tambah Subnet';
    document.getElementById('subnetModalDesc').textContent = 'Tambah data subnet jaringan baru';

    openModal('modal-subnet');
}

function openSubnetEdit(btn) {
    const form = document.getElementById('form-subnet');

    document.getElementById('subnet-id').value         = btn.dataset.id || '';
    document.getElementById('subnet-cidr').value       = btn.dataset.cidr || '';
    document.getElementById('subnet-prefix').value     = btn.dataset.prefix || '';
    document.getElementById('subnet-gateway').value    = btn.dataset.gateway || '';
    document.getElementById('subnet-mask').value       = btn.dataset.mask || '';
    document.getElementById('subnet-keterangan').value = btn.dataset.keterangan || '';
    form.action = (window.BASE_URL || '') + '/subnet/simpan';

    document.getElementById('subnetModalIcon').textContent = 'edit';
    document.getElementById('subnetModalTitle').textContent = 'Edit Subnet';
    document.getElementById('subnetModalDesc').textContent = 'Perbarui data subnet';

    openModal('modal-subnet');
}

function confirmSubnetDelete(id, cidr) {
    openConfirmModal({
        icon: 'delete',
        title: 'Hapus Subnet',
        text: 'Yakin hapus subnet "' + cidr + '"? Subnet yang masih dipakai alokasi IP tidak akan terhapus.',
        confirmLabel: 'Ya, Hapus',
        variant: 'red',
        onConfirm: function () {
            window.location.href = (window.BASE_URL || '') + '/subnet/hapus/' + id;
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
