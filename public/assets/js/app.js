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

    const applyTheme = function (next) {
        root.setAttribute('data-theme', next);
        document.cookie = 'theme=' + next + '; path=/; max-age=' + (365 * 24 * 3600) + '; samesite=lax';
        paint(next);

        document.dispatchEvent(new CustomEvent('themechange', { detail: { theme: next } }));
    };

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';

            // User minta minim animasi: langsung tanpa transisi
            if (reduceMotion) {
                applyTheme(next);
                return;
            }

            // Browser tanpa View Transitions (mis. Firefox): morph warna via CSS
            if (!document.startViewTransition) {
                root.classList.add('theme-transition');
                applyTheme(next);
                window.setTimeout(function () {
                    root.classList.remove('theme-transition');
                }, 400);
                return;
            }

            const rect = btn.getBoundingClientRect();
            const x = rect.left + rect.width / 2;
            const y = rect.top + rect.height / 2;
            const endRadius = Math.hypot(
                Math.max(x, window.innerWidth - x),
                Math.max(y, window.innerHeight - y)
            );

            const transition = document.startViewTransition(function () {
                applyTheme(next);
            });

            transition.ready.then(function () {
                document.documentElement.animate(
                    {
                        clipPath: [
                            'circle(0px at ' + x + 'px ' + y + 'px)',
                            'circle(' + endRadius + 'px at ' + x + 'px ' + y + 'px)'
                        ]
                    },
                    {
                        duration: 500,
                        easing: 'ease-in-out',
                        pseudoElement: '::view-transition-new(root)'
                    }
                );
            }).catch(function () { /* transisi dilewati, abaikan */ });
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
