<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/email.php';
requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$pdo = Database::getInstance();
$allowed = ['site_name','site_logo','site_favicon','header_text','footer_copyright','breaking_news','email_provider','smtp_host','smtp_port','smtp_encryption','smtp_username','smtp_password','smtp_from_name','smtp_from_email','site_url','admin_alert_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach ($_POST['setting'] ?? [] as $key => $value) {
        if (!in_array((string) $key, $allowed, true)) continue;
        $value = trim((string) $value);
        if ($key === 'smtp_port' && (!ctype_digit($value) || (int) $value < 1 || (int) $value > 65535)) continue;
        if ($key === 'smtp_encryption' && !in_array($value, ['tls','ssl','none'], true)) continue;
        if (in_array($key, ['smtp_from_email','admin_alert_email'], true) && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) continue;
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value_update, updated_at = NOW()');
        $stmt->execute([':key' => $key, ':value' => $value, ':value_update' => $value]);
    }
    setFlash('success', 'Settings updated successfully.');
    redirect('settings.php');
}

$settings = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
$map = [];
foreach ($settings as $row) $map[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
$flash = getFlash();
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Site Settings | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5"><div class="d-flex justify-content-between align-items-center mb-4"><h2>Site Settings</h2><a href="index.php" class="btn btn-outline-secondary">Dashboard</a></div>
<?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
<form method="post" class="row g-3"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="col-md-6"><label class="form-label">Site Name</label><input class="form-control" name="setting[site_name]" value="<?= e($map['site_name'] ?? APP_NAME) ?>"></div>
<div class="col-md-6"><label class="form-label">Site URL</label><input class="form-control" type="url" name="setting[site_url]" value="<?= e($map['site_url'] ?? '') ?>" required></div>
<div class="col-md-6"><label class="form-label">Email Provider</label><select class="form-select" name="setting[email_provider]"><option value="zoho" <?= (($map['email_provider'] ?? 'zoho') === 'zoho') ? 'selected' : '' ?>>Zoho</option><option value="gmail" <?= (($map['email_provider'] ?? '') === 'gmail') ? 'selected' : '' ?>>Gmail</option><option value="smtp" <?= (($map['email_provider'] ?? '') === 'smtp') ? 'selected' : '' ?>>Custom SMTP</option></select></div>
<div class="col-md-6"><label class="form-label">Admin Alert Email</label><input class="form-control" type="email" name="setting[admin_alert_email]" value="<?= e($map['admin_alert_email'] ?? '') ?>"></div>
<div class="col-md-6"><label class="form-label">SMTP Host</label><input class="form-control" name="setting[smtp_host]" value="<?= e($map['smtp_host'] ?? 'smtp.zoho.com') ?>"></div>
<div class="col-md-3"><label class="form-label">SMTP Port</label><input class="form-control" type="number" name="setting[smtp_port]" value="<?= e($map['smtp_port'] ?? '587') ?>"></div>
<div class="col-md-3"><label class="form-label">Encryption</label><select class="form-select" name="setting[smtp_encryption]"><option value="tls">TLS</option><option value="ssl">SSL</option><option value="none">None</option></select></div>
<div class="col-md-6"><label class="form-label">SMTP Username</label><input class="form-control" name="setting[smtp_username]" value="<?= e($map['smtp_username'] ?? '') ?>"></div>
<div class="col-md-6"><label class="form-label">SMTP App Password</label><input class="form-control" type="password" name="setting[smtp_password]" value="<?= e($map['smtp_password'] ?? '') ?>" autocomplete="new-password"></div>
<div class="col-md-6"><label class="form-label">From Name</label><input class="form-control" name="setting[smtp_from_name]" value="<?= e($map['smtp_from_name'] ?? APP_NAME) ?>"></div>
<div class="col-md-6"><label class="form-label">From Email</label><input class="form-control" type="email" name="setting[smtp_from_email]" value="<?= e($map['smtp_from_email'] ?? '') ?>"></div>
<div class="col-12"><button class="btn btn-primary" type="submit">Save Settings</button></div></form></main></body></html>
