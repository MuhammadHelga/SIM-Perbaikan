<?php
/**
 * src/views/errors/screens/404View.php
 * Ditampilkan lewat index.php saat route tidak dikenali (http_response_code(404)).
 */
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | SIM-Perbaikan</title>
    <link rel="icon" href="<?= BASE_URL ?>/favicon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#00288e">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/404.css">
</head>
<body>

<button type="button" class="theme-toggle" aria-label="Ganti tema">
    <span class="material-symbols-outlined">dark_mode</span>
</button>

<section class="wrapper">
    <div class="container">

        <div id="scene" class="scene" data-hover-only="false">

            <div class="circle" data-depth="1.2"></div>

            <div class="one" data-depth="0.9">
                <div class="content">
                    <span class="piece"></span>
                    <span class="piece"></span>
                    <span class="piece"></span>
                </div>
            </div>

            <div class="two" data-depth="0.60">
                <div class="content">
                    <span class="piece"></span>
                    <span class="piece"></span>
                    <span class="piece"></span>
                </div>
            </div>

            <div class="three" data-depth="0.40">
                <div class="content">
                    <span class="piece"></span>
                    <span class="piece"></span>
                    <span class="piece"></span>
                </div>
            </div>

            <p class="p404" data-depth="0.50">404</p>
            <p class="p404" data-depth="0.10">404</p>

        </div>

        <div class="text">
            <article>
                <h1>Halaman Tidak Ditemukan</h1>
                <p>Sepertinya halaman yang kamu cari tidak tersedia<br>atau sudah dipindahkan.</p>
                <div class="btns">
                    <a href="<?= BASE_URL ?>/dashboard" class="btn-404">Kembali ke Dashboard</a>
                    <button type="button" class="btn-404 btn-404--outline" onclick="history.back()">Halaman Sebelumnya</button>
                </div>
            </article>
        </div>

    </div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/parallax/3.1.0/parallax.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/404.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
</body>
</html>