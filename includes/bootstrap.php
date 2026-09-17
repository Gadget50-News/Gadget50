<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$configPath = __DIR__ . '/../config.php';
$lockPath = __DIR__ . '/../install.lock';

if (!is_file($configPath) || !is_file($lockPath)) {
    header('Location: ../install.php');
    exit;
}

require_once $configPath;

if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS') || !defined('APP_NAME')) {
    header('Location: ../install.php');
    exit;
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
