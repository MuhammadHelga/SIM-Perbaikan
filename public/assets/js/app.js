document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initFlash();
    initFilterFocus();
    initYearStepper();
    initSidebar();
    initNavIndicator();
    initSubmitGuard();
});

/* Garis indikator di kanan: meluncur ke item aktif / yang diklik */
function initNavIndicator() {
    const menu = document.querySelector('.app-navbar__menu');
    const indicator = document.getElementById('navIndicator');
    if (!menu || !indicator) return;

    const links = Array.prototype.slice.call(menu.querySelectorAll('.app-navbar__link'));

    const place = function (el, animate) {
        if (!el) return;
        if (!animate) indicator.style.transition = 'none';
        indicator.style.top = el.offsetTop + 'px';
        indicator.style.height = el.offsetHeight + 'px';
        if (!animate) {
            void indicator.offsetHeight; // paksa reflow
            indicator.style.transition = '';
        }
    };

    const active = menu.querySelector('.app-navbar__link.is-active');
    place(active || links[0], false);

    window.requestAnimationFrame(function () {
        window.setTimeout(function () { indicator.classList.add('is-ready'); }, 60);
    });

    links.forEach(function (link) {
        link.addEventListener('click', function (e) {
            if (link.classList.contains('is-active')) return; // halaman sama, biarkan normal
            e.preventDefault();
            place(link, true);
            window.setTimeout(function () { window.location.href = link.href; }, 280);
        });
    });

    window.addEventListener('resize', function () {
        place(menu.querySelector('.app-navbar__link.is-active'), false);
    });
}

/* Cegah kirim form dua kali: nonaktifkan tombol submit setelah submit */
function initSubmitGuard() {
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        const btn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (!btn) return;

        window.setTimeout(function () {
            if (e.defaultPrevented) return; // dibatalkan handler lain (mis. validasi)
            btn.disabled = true;
            btn.style.opacity = '0.65';
            btn.style.cursor = 'not-allowed';
        }, 0);
    }, true);
}

/* Sidebar: lipat (desktop) + off-canvas (mobile) */
function initSidebar() {
    const body = document.body;
    const collapse = document.getElementById('navCollapse');
    const toggle = document.getElementById('navToggle');
    const backdrop = document.getElementById('navBackdrop');

    if (collapse) {
        collapse.addEventListener('click', function () {
            body.classList.toggle('nav-collapsed');
            try {
                localStorage.setItem('sidebarCollapsed', body.classList.contains('nav-collapsed') ? '1' : '0');
            } catch (e) {}
        });
    }

    const isMobile = function () {
        return window.matchMedia('(max-width: 992px)').matches;
    };

    if (toggle) {
        toggle.addEventListener('click', function () {
            body.classList.toggle('nav-open');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            body.classList.remove('nav-open');
        });
    }

    document.querySelectorAll('.app-navbar__link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobile()) body.classList.remove('nav-open');
        });
    });

    window.addEventListener('resize', function () {
        if (!isMobile()) body.classList.remove('nav-open');
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') body.classList.remove('nav-open');
    });
}

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
