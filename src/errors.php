<?php

/**
 * Penanganan error terpusat.
 *
 * - Detail (pesan, file, baris, stack trace) hanya masuk log, tidak ke pengguna.
 * - Pengguna menerima halaman 500 (src/views/errors/screens/500View.php).
 */

function app_log_error(Throwable $e): void
{
    error_log(sprintf(
        "[%s] %s: %s in %s:%d\nStack trace:\n%s",
        date('Y-m-d H:i:s'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ));
}

function app_render_500(?Throwable $error = null): void
{
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    require __DIR__ . '/views/errors/screens/500View.php';
}

/** Handler untuk exception yang tidak tertangkap. */
function app_handle_exception(Throwable $e): void
{
    $GLOBALS['__app_error_handled'] = true;

    app_log_error($e);
    app_render_500($e);

    exit;
}

/** Handler untuk fatal error (dipanggil saat shutdown). */
function app_handle_fatal(): void
{
    if (!empty($GLOBALS['__app_error_handled'])) {
        return; // sudah ditangani handler exception
    }

    $err = error_get_last();
    if ($err === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($err['type'], $fatalTypes, true)) {
        return;
    }

    $GLOBALS['__app_error_handled'] = true;

    $e = new ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']);
    app_log_error($e);
    app_render_500($e);
}
