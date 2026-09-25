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
    }
});