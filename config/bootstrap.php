<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('APP_URL')) {
    define('APP_URL', '/petvida');
}

if (!defined('DB_PATH')) {
    define('DB_PATH', APP_ROOT . '/storage/petvida.sqlite');
}

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';

initializeDatabase();
