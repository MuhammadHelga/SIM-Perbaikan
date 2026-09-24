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
    define('DB_PASS', 'myadminiscj7');
    define('DB_NAME', 'lkah');
?>
