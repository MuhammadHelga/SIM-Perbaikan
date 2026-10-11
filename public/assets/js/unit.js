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

    initUnitTableSync('ruanganScroll', 'ruanganHeadWrap');
    initUnitTableSync('barangScroll',  'barangHeadWrap');
    initUnitTableSync('asetScroll',    'asetHeadWrap');
});

function initUnitTableSync(scrollId, headId) {
    const scroll  = document.getElementById(scrollId);
    const headWrap = document.getElementById(headId);
    if (!scroll || !headWrap) return;

    function syncPadding() {
        const gutter = scroll.offsetWidth - scroll.clientWidth;
        headWrap.style.paddingRight = gutter > 0 ? gutter + 'px' : '';
    }
    syncPadding();
    window.addEventListener('resize', syncPadding);
    if (window.ResizeObserver) {
        new ResizeObserver(syncPadding).observe(scroll);
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
            postAction((window.BASE_URL || '') + '/unit/' + jenis + '/hapus/' + id);
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

// ===== Aset Barang (unit fisik per ruangan) =====

function openAsetModal() {
    const form = document.getElementById('form-aset');
    form.reset();
    document.getElementById('aset-id').value = '';
    form.action = (window.BASE_URL || '') + '/unit/aset/simpan';

    document.getElementById('asetModalIcon').textContent = 'devices';
    document.getElementById('asetModalTitle').textContent = 'Tambah Aset Barang';
    document.getElementById('asetModalDesc').textContent = 'Daftarkan unit fisik barang di suatu ruangan';

    openModal('modal-aset');
}

function openAsetEdit(btn) {
    const form = document.getElementById('form-aset');

    document.getElementById('aset-id').value          = btn.dataset.id || '';
    document.getElementById('aset-ruangan').value     = btn.dataset.idRuangan || '';
    document.getElementById('aset-barang').value      = btn.dataset.idBarang || '';
    document.getElementById('aset-serial').value      = btn.dataset.serial || '';
    document.getElementById('aset-tipe').value        = btn.dataset.tipe || '';
    document.getElementById('aset-keterangan').value  = btn.dataset.keterangan || '';
    form.action = (window.BASE_URL || '') + '/unit/aset/simpan';

    document.getElementById('asetModalIcon').textContent = 'edit';
    document.getElementById('asetModalTitle').textContent = 'Edit Aset Barang';
    document.getElementById('asetModalDesc').textContent = 'Perbarui data unit fisik barang';

    openModal('modal-aset');
}

function confirmAsetDelete(id, serial) {
    openConfirmModal({
        icon: 'delete',
        title: 'Hapus Aset Barang',
        text: 'Yakin hapus aset "' + serial + '"? Laporan yang pernah memakainya tetap utuh.',
        confirmLabel: 'Ya, Hapus',
        variant: 'red',
        onConfirm: function () {
            postAction((window.BASE_URL || '') + '/unit/aset/hapus/' + id);
        }
    });
}
