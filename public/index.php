<?php

// ===== Sesi: hardening cookie (harus sebelum session_start) =====
if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $isHttps,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// ===== Header keamanan (via PHP, tanpa perlu mod_headers) =====
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/conf.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../src/helpers.php';

// ===== CSRF token per session =====
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$conn = db();

require_once __DIR__ . '/../src/services/AuthService.php';
$authService = new AuthService($conn);
$authService->loginByCookie();

$basePath = BASE_URL;

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path       = str_replace($basePath, '', $requestUri);

// ===== Routing =====
require_once __DIR__ . '/../src/Router.php';
$router = new Router();
require __DIR__ . '/../src/routes.php';

if (!$router->dispatch($_SERVER['REQUEST_METHOD'], $path)) {
    http_response_code(404);
    require __DIR__ . '/../src/views/errors/screens/404View.php';
}
