<?php
/**
 * Halaman error 405 (metode HTTP tidak diizinkan).
 * Sengaja minimal: tidak memuat aset eksternal.
 */
$base = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>405 - Metode Tidak Diizinkan | SIM-Perbaikan</title>
    <link rel="icon" href="<?= htmlspecialchars($base) ?>/favicon.svg" type="image/svg+xml">
    <meta name="theme-color" content="#00288e">
    <style>
        :root { --primary: #00288e; --text: #1e293b; --muted: #64748b; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #f8fafc;
            color: var(--text);
        }
        .card {
            width: min(520px, 100%);
            padding: 36px 28px;
            text-align: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        }
        .code { margin: 0 0 8px; font-size: 56px; font-weight: 800; color: var(--primary); }
        h1 { margin: 0 0 10px; font-size: 22px; }
        p { margin: 0 0 22px; line-height: 1.6; color: var(--muted); }
        a {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 8px;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover { opacity: .9; }
    </style>
</head>
<body>
    <main class="card">
        <p class="code">405</p>
        <h1>Metode Permintaan Tidak Diizinkan</h1>
        <p>
            Halaman ini tidak menerima metode HTTP tersebut.
            Kembali ke dashboard dan gunakan navigasi aplikasi.
        </p>
        <a href="<?= htmlspecialchars($base) ?>/dashboard">Kembali ke Dashboard</a>
    </main>
</body>
</html>
