document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initFlash();
    initFilterFocus();
    initYearStepper();
});

/* Toggle tema terang/gelap. Disimpan di cookie 'theme', tanpa reload. */
function initTheme() {
    const root = document.documentElement;
    const btns = document.querySelectorAll('.theme-toggle');
    if (!btns.length) return;

    const current = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';

    const paint = function (theme) {
        btns.forEach(function (btn) {
            const icon = btn.querySelector('.material-symbols-outlined');
            if (icon) icon.textContent = (theme === 'dark') ? 'light_mode' : 'dark_mode';
            btn.setAttribute('aria-label', theme === 'dark' ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
        });
    };

    paint(current);

    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            document.cookie = 'theme=' + next + '; path=/; max-age=' + (365 * 24 * 3600) + '; samesite=lax';
            paint(next);

            if (typeof window.onThemeChange === 'function') {
                window.onThemeChange(next);
            }
        });
    });
}

/* Toast pesan flash: auto-hilang, pause saat hover, tombol close */
function initFlash() {
    const el = document.querySelector('.flash');
    if (!el) return;

    let timer;
    const hide = function () {
        el.classList.add('flash--hide');
        setTimeout(function () { el.remove(); }, 300);
    };
    const start = function () { timer = setTimeout(hide, 3000); };

    start();
    el.addEventListener('mouseenter', function () { clearTimeout(timer); });
    el.addEventListener('mouseleave', start);

    const closeBtn = el.querySelector('.flash__close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            clearTimeout(timer);
            hide();
        });
    }
}

/* Kembalikan fokus ke kolom cari (#search) setelah reload, kursor di akhir */
function initFilterFocus() {
    const el = document.getElementById('search');
    if (!el || el.value === '') return;

    el.focus();
    if (typeof el.setSelectionRange === 'function') {
        const len = el.value.length;
        el.setSelectionRange(len, len);
    }
}

/* Year stepper: ‹ Tahun 2026 › (data tahun dari atribut data-years) */
function initYearStepper() {
    const stepper = document.querySelector('.year-stepper');
    if (!stepper) return;

    const form   = stepper.closest('form');
    const hidden = document.getElementById('tahunValue');
    const prev   = document.getElementById('tahunPrev');
    const next   = document.getElementById('tahunNext');
    const years  = (stepper.dataset.years || '')
        .split(',')
        .map(function (y) { return parseInt(y, 10); })
        .filter(function (y) { return !isNaN(y); });

    if (!form || !hidden || !years.length) return;

    let idx = years.indexOf(parseInt(hidden.value, 10));
    if (idx < 0) idx = 0;

    const go = function (i) {
        if (i < 0 || i >= years.length) return;
        hidden.value = years[i];
        form.requestSubmit();
    };

    if (prev) {
        prev.addEventListener('click', function () { go(idx + 1); }); // ‹ tahun lebih lama
        prev.disabled = (idx + 1 >= years.length);
    }
    if (next) {
        next.addEventListener('click', function () { go(idx - 1); }); // › tahun lebih baru
        next.disabled = (idx - 1 < 0);
    }
}
