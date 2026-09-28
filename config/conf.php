<?php
    define('BASE_PATH', dirname(__DIR__));

    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if (substr($base, -7) === '/public') {
        $base = substr($base, 0, -7);
    }
    define('BASE_URL', rtrim($base, '/'));
    
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', 3306);
    define('DB_USER', 'root');
    // define('DB_PASS', 'myadminiscj7');
    define('DB_PASS', '');
    // define('DB_PASS', '');
    // define('DB_PASS', '');
    // define('DB_PASS', '');
    define('DB_NAME', 'lkah');

    // Kunci untuk menandatangani cookie "Ingat akun saya" (rahasia)
    define('APP_SECRET', 'd1efa12fe7bd8de02c54aeb0432a90b561c5f665aca2fb1dd0843af5e2b254e7');
?>
