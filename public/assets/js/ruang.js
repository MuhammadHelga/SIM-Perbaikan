document.addEventListener('DOMContentLoaded', function () {
    const scrollBody = document.getElementById('tableScrollBody');
    const scrollFooter = document.getElementById('tableFooterScroll');

    if (scrollBody && scrollFooter) {
        scrollBody.addEventListener('scroll', function () {
            scrollFooter.scrollLeft = scrollBody.scrollLeft;
        });
        scrollFooter.addEventListener('scroll', function () {
            scrollBody.scrollLeft = scrollFooter.scrollLeft;
        });

        // Body menyediakan ruang scrollbar (agar layout stabil); footer disamakan
        // supaya kolom baris TOTAL tetap sejajar dengan kolom body.
        const alignGutter = function () {
            const gutter = scrollBody.offsetWidth - scrollBody.clientWidth;
            scrollFooter.style.paddingRight = gutter > 0 ? gutter + 'px' : '';
        };
        alignGutter();
        window.addEventListener('resize', alignGutter);

        if (window.ResizeObserver) {
            new ResizeObserver(alignGutter).observe(scrollBody);
        }
    }
});