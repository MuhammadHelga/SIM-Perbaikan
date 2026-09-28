document.addEventListener('DOMContentLoaded', function () {
    const scrollBody   = document.getElementById('tableScrollBody');
    const scrollFooter = document.getElementById('tableFooterScroll');
    const headWrap     = document.getElementById('rekapHeadWrap');

    if (scrollBody && scrollFooter) {
        scrollBody.addEventListener('scroll', function () {
            scrollFooter.scrollLeft = scrollBody.scrollLeft;
            if (headWrap) headWrap.scrollLeft = scrollBody.scrollLeft;
        });
        scrollFooter.addEventListener('scroll', function () {
            scrollBody.scrollLeft = scrollFooter.scrollLeft;
            if (headWrap) headWrap.scrollLeft = scrollFooter.scrollLeft;
        });

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