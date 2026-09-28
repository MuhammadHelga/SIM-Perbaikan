<?php

/**
 * Rute autentikasi: /login, /logout.
 */

return function (Router $router, mysqli $conn, string $basePath, AuthService $authService): void {
    $router->any('/login', function () use ($basePath, $authService) {
        if (!empty($_SESSION['is_logged_in'])) {
            redirect($basePath . '/dashboard');
        }

        $errorMessage = null;
        $oldUsername  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $now      = time();

            if (!csrfValid()) {
                $errorMessage = 'Sesi tidak valid. Silakan coba lagi.';
                $oldUsername  = $username;
            } elseif (($_SESSION['login_lock_until'] ?? 0) > $now) {
                $sisa = (int) ($_SESSION['login_lock_until'] - $now);
                $errorMessage = "Terlalu banyak percobaan. Coba lagi dalam {$sisa} detik.";
                $oldUsername  = $username;
            } elseif ($username === '' || $password === '') {
                $errorMessage = 'Username dan password wajib diisi!';
                $oldUsername  = $username;
            } elseif ($authService->login($username, $password, !empty($_POST['remember']))) {
                unset($_SESSION['login_fail'], $_SESSION['login_lock_until']);
                redirect($basePath . '/dashboard');
            } else {
                $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
                if ($_SESSION['login_fail'] >= 5) {
                    $_SESSION['login_lock_until'] = $now + 60;
                    $_SESSION['login_fail'] = 0;
                }
                $errorMessage = 'Username atau password salah!';
                $oldUsername  = $username;
            }
        }

        require __DIR__ . '/../views/login/screens/LoginView.php';
    });

    $router->any('/logout', function () use ($basePath, $authService) {
        if (!csrfValid()) {
            flash('error', 'Sesi tidak valid. Silakan coba lagi.');
            redirect($basePath . '/dashboard');
        }

        $authService->logout();
        redirect($basePath . '/login');
    });
};
