<?php
function db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $conn->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        if (function_exists('app_log_error') && function_exists('app_render_500')) {
            app_log_error($e);
            app_render_500($e);
        } else {
            http_response_code(500);
            echo 'Koneksi database gagal.';
        }
        exit;
    }

    return $conn;
}
?>
