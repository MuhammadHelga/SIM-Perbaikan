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

// ===== Penanganan error: detail ke log, pengguna melihat halaman 500 =====
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/../src/errors.php';
set_exception_handler('app_handle_exception');
register_shutdown_function('app_handle_fatal');

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

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Potong base path hanya di awal URL, dengan batas "/" agar "/sim" tidak
// memotong "/simulasi".
$path = $requestUri;
if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
    $path = substr($path, strlen($basePath));
    if ($path === '' || $path === false) {
        $path = '/';
    }
}

// ===== Routing =====
require_once __DIR__ . '/../src/Router.php';
$router = new Router();

/** @var callable $registerRoutes */
$registerRoutes = require __DIR__ . '/../src/routes.php';
$registerRoutes($router, $conn, $basePath, $authService);

$routeResult = $router->dispatch($_SERVER['REQUEST_METHOD'], $path);

if ($routeResult === Router::NOT_FOUND) {
    http_response_code(404);
    require __DIR__ . '/../src/views/errors/screens/404View.php';
} elseif ($routeResult === Router::METHOD_NOT_ALLOWED) {
    http_response_code(405);
    require __DIR__ . '/../src/views/errors/screens/405View.php';
}
