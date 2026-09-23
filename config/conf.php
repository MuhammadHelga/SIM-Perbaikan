<?php
    define('BASE_PATH', dirname(__DIR__));

    define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'));
?>