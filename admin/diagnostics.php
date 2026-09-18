<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$checks = [];
$checks['PHP version'] = [version_compare(PHP_VERSION, '8.0.0', '>='), PHP_VERSION];
foreach (['PDO','pdo_mysql','openssl','json','fileinfo','mbstring','session'] as $extension) {
    $checks['Extension: ' . $extension] = [extension_loaded($extension), extension_loaded($extension) ? 'Available' : 'Missing'];
}
foreach ([__DIR__ . '/../config.php', __DIR__ . '/../install.lock', __DIR__ . '/../uploads'] as $path) {
    $checks['Writable: ' . basename($path)] = [is_writable($path), is_writable($path) ? 'Writable' : 'Not writable or missing'];
}
$pdoOk = true;
try { Database::getInstance()->query('SELECT 1'); } catch (Throwable $e) { $pdoOk = false; }
$checks['Database connection'] = [$pdoOk, $pdoOk ? 'Connected' : 'Unavailable'];
$checks['Session'] = [session_status() === PHP_SESSION_ACTIVE, session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'];
$checks['Email service'] = [emailServiceAvailable(), emailServiceAvailable() ? 'Enabled and SMTP-validated' : 'Disabled or not validated'];
$flash = getFlash();
?><!doctype html><html lang="bn"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Hosting Diagnostics</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><main class="container py-5"><div class="d-flex justify-content-between align-items-center mb-4"><h1>Hosting Diagnostics</h1><a href="index.php" class="btn btn-outline-secondary">Dashboard</a></div><div class="alert alert-info">এই রিপোর্টে কোনো database password, SMTP password বা ব্যক্তিগত configuration দেখানো হয় না।</div><div class="card"><div class="card-body"><table class="table align-middle"><thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead><tbody><?php foreach ($checks as $name => [$ok, $detail]): ?><tr><td><?= e($name) ?></td><td><span class="badge text-bg-<?= $ok ? 'success' : 'danger' ?>"><?= $ok ? 'OK' : 'FAIL' ?></span></td><td><?= e((string) $detail) ?></td></tr><?php endforeach; ?></tbody></table></div></div></main></body></html>
