<?php
$errorMessage = $errorMessage ?? null;
$oldUsername  = $oldUsername ?? '';
?>

<!DOCTYPE html>
<html lang="en" data-theme="<?= ($_COOKIE['theme'] ?? 'light') === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM-Perbaikan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,400,0,0" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css">
    <script src="<?= BASE_URL ?>/assets/js/login.js" defer></script>
</head>
<body>
    <button type="button" class="theme-toggle theme-toggle--floating" aria-label="Ganti tema">
        <span class="material-symbols-outlined">dark_mode</span>
    </button>
    <div class="login-page">
        <section class="login-form-side">
            <div class="login-card">
                <div class="login-card__accent" aria-hidden="true"></div>
                <div class="login-card__brand">
                    <h1>SIM-Perbaikan</h1>
                    <p>Sistem Laporan Pemeliharaan<br>Kerusakan Faskes RS Al-Huda</p>
                    <h2 class="login-card__title">Masuk ke Sistem Laporan</h2>
                    <?php if ($errorMessage): ?>
                        <div class="login-alert" role="alert">
                            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                    <form class="login-form" method="post" action="<?= BASE_URL ?>/login" autocomplete="on">
                        <div class="field">
                            <label for="username">Username</label>
                            <div class="field__input">
                                <span class="field__icon material-symbols-outlined" aria-hidden="true">
                                    person
                                </span>
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    placeholder="Masukkan username"
                                    value="<?= htmlspecialchars($oldUsername, ENT_QUOTES, 'UTF-8') ?>"
                                    autocomplete="username"
                                    required
                                    autofocus
                                >
                            </div>
                        </div>
                        <div class="field">
                            <label for="password">Password</label>
                            <div class="field__input">
                                <span class="field__icon material-symbols-outlined" aria-hidden="true">
                                    lock
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Masukkan password"
                                    autocomplete="current-password"
                                    required
                                >
                                <button type="button" class="field__toggle" id="togglePassword" aria-label="Tampilkan password" aria-pressed="false">
                                    <span class="material-symbols-outlined" id="toggleIcon" aria-hidden="true">visibility</span>
                                </button>
                            </div>
                        </div>

                        <label class="checkbox">
                            <input type="checkbox" name="remember" value="1">
                            <span>Ingat akun saya pada perangkat ini</span>
                        </label>

                        <button type="submit" class="btn-submit">Masuk</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="login-visual-side" aria-hidden="true">
            <img src="<?= BASE_URL ?>/assets/images/bg_alhuda.jpg" alt="gambar rs al-huda" class="photos">
        </section>
    </div>
</body>
</html>
