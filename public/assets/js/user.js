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

function openUserModal() {
    const form = document.getElementById('form-user');
    form.reset();
    document.getElementById('user-id').value = '';
    form.action = (window.BASE_URL || '') + '/user/simpan';

    const passwordInput = document.getElementById('user-password');
    passwordInput.required = true;
    passwordInput.placeholder = 'Minimal 6 karakter';
    document.getElementById('userPasswordHint').textContent = '';

    document.getElementById('userModalIcon').textContent = 'person_add';
    document.getElementById('userModalTitle').textContent = 'Tambah User';
    document.getElementById('userModalDesc').textContent = 'Buat akun user baru';

    openModal('modal-user');
}

function openUserEdit(btn) {
    const form = document.getElementById('form-user');

    document.getElementById('user-id').value       = btn.dataset.id || '';
    document.getElementById('user-nama').value     = btn.dataset.nama || '';
    document.getElementById('user-jabatan').value  = btn.dataset.jabatan || '';
    document.getElementById('user-username').value = btn.dataset.username || '';
    document.getElementById('user-role').value     = btn.dataset.role || 'teknisi';
    form.action = (window.BASE_URL || '') + '/user/simpan';

    const passwordInput = document.getElementById('user-password');
    passwordInput.required = false;
    passwordInput.placeholder = 'Kosongkan jika tidak diganti';
    document.getElementById('userPasswordHint').textContent =
        'Biarkan kosong jika password tidak ingin diganti. Isi hanya bila ingin mengganti password.';

    document.getElementById('userModalIcon').textContent = 'edit';
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('userModalDesc').textContent = 'Perbarui data user';

    openModal('modal-user');
}

function confirmUserDelete(id, username) {
    openConfirmModal({
        icon: 'delete',
        title: 'Hapus User',
        text: 'Yakin hapus user "' + username + '"?',
        confirmLabel: 'Ya, Hapus',
        variant: 'red',
        onConfirm: function () {
            postAction((window.BASE_URL || '') + '/user/hapus/' + id);
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
