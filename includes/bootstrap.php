<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $root = dirname($script);
    if (str_ends_with($root, '/admin')) $root = dirname($root);
    $cookiePath = ($root === '/' || $root === '.' || $root === '\\') ? '/' : '/' . trim($root, '/') . '/';
    session_set_cookie_params(['lifetime'=>0,'path'=>$cookiePath,'secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$configPath = __DIR__ . '/../config.php';
$lockPath = __DIR__ . '/../install.lock';
if (!is_file($configPath) || !is_file($lockPath)) {
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = dirname($script);
    if (str_ends_with($base, '/admin')) $base = dirname($base);
    header('Location: ' . (($base === '/' || $base === '.') ? '' : rtrim($base, '/')) . '/install.php');
    exit;
}
require_once $configPath;
$debug = defined('APP_DEBUG') && APP_DEBUG === true;
error_reporting($debug ? E_ALL : E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS') || !defined('APP_NAME')) {
    http_response_code(500);
    exit('Application configuration is incomplete.');
}
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com 'unsafe-inline' data: blob:");
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
}
