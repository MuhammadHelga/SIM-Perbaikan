document.addEventListener('DOMContentLoaded', function () {
    var scene = document.getElementById('scene');

    // Jaga-jaga kalau library parallax.js gagal load (misal tidak ada internet) —
    // halaman tetap tampil normal, cuma tanpa efek parallax mouse-nya.
    if (scene && typeof Parallax !== 'undefined') {
        new Parallax(scene);
    }
});