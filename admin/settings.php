<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/email.php';
requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$pdo = Database::getInstance();
$allowed = ['site_name', 'site_logo', 'site_favicon', 'header_text', 'footer_copyright', 'breaking_news', 'email_provider', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password', 'smtp_from_name', 'smtp_from_email', 'site_url', 'admin_alert_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['action'] ?? '') === 'test_email') {
        $to = trim((string) ($_POST['test_email'] ?? ''));
        $ok = filter_var($to, FILTER_VALIDATE_EMAIL) && sendEmail($to, APP_NAME . ' SMTP test', '<p>SMTP test successful.</p>');
        setFlash($ok ? 'success' : 'danger', $ok ? 'Test email sent successfully.' : 'Test email failed. Check SMTP settings and server logs.');
        redirect('settings.php');
    }
    foreach ($_POST['setting'] ?? [] as $key => $value) {
        $key = (string) $key;
        if (!in_array($key, $allowed, true)) continue;
        $value = trim((string) $value);
        if ($key === 'smtp_password' && $value === '') continue;
        if ($key === 'smtp_port' && (!ctype_digit($value) || (int) $value < 1 || (int) $value > 65535)) continue;
        if ($key === 'smtp_encryption' && !in_array($value, ['tls', 'ssl', 'none'], true)) continue;
        if (in_array($key, ['smtp_from_email', 'admin_alert_email'], true) && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) continue;
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value_update, updated_at = CURRENT_TIMESTAMP');
        $stmt->execute([':key' => $key, ':value' => $value, ':value_update' => $value]);
    }
    setFlash('success', 'Settings updated successfully.');
    redirect('settings.php');
}

$rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
$settings = [];
foreach ($rows as $row) $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
$flash = getFlash();
function settingValue(array $settings, string $key, string $default = ''): string { return $settings[$key] ?? $default; }
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Site Settings | <?= e(APP_NAME) ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5"><div class="d-flex justify-content-between align-items-center mb-4"><h2>Site Settings</h2><a href="index.php" class="btn btn-outline-secondary">Dashboard</a></div>
<?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
<form method="post" class="row g-3"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
<div class="col-md-6"><label class="form-label">Site name</label><input class="form-control" name="setting[site_name]" value="<?= e(settingValue($settings, 'site_name', APP_NAME)) ?>"></div>
<div class="col-md-6"><label class="form-label">Site URL</label><input class="form-control" type="url" name="setting[site_url]" value="<?= e(settingValue($settings, 'site_url')) ?>" required></div>
<div class="col-md-6"><label class="form-label">SMTP host</label><input class="form-control" name="setting[smtp_host]" value="<?= e(settingValue($settings, 'smtp_host', 'smtp.zoho.com')) ?>"></div>
<div class="col-md-3"><label class="form-label">SMTP port</label><input class="form-control" type="number" name="setting[smtp_port]" value="<?= e(settingValue($settings, 'smtp_port', '587')) ?>"></div>
<div class="col-md-3"><label class="form-label">Encryption</label><select class="form-select" name="setting[smtp_encryption]"><option value="tls" <?= settingValue($settings, 'smtp_encryption', 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option><option value="ssl" <?= settingValue($settings, 'smtp_encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option><option value="none" <?= settingValue($settings, 'smtp_encryption') === 'none' ? 'selected' : '' ?>>None</option></select></div>
<div class="col-md-6"><label class="form-label">SMTP username</label><input class="form-control" name="setting[smtp_username]" value="<?= e(settingValue($settings, 'smtp_username')) ?>"></div>
<div class="col-md-6"><label class="form-label">SMTP app password</label><input class="form-control" type="password" name="setting[smtp_password]" autocomplete="new-password"><div class="form-text">Leave blank to keep the existing password.</div></div>
<div class="col-md-6"><label class="form-label">From email</label><input class="form-control" type="email" name="setting[smtp_from_email]" value="<?= e(settingValue($settings, 'smtp_from_email')) ?>"></div>
<div class="col-md-6"><label class="form-label">Admin alert email</label><input class="form-control" type="email" name="setting[admin_alert_email]" value="<?= e(settingValue($settings, 'admin_alert_email')) ?>"></div>
<div class="col-12"><button class="btn btn-primary" type="submit">Save settings</button></div></form>
<hr class="my-4"><h4>Test SMTP</h4><form method="post" class="row g-3"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="test_email"><div class="col-md-8"><input class="form-control" type="email" name="test_email" placeholder="recipient@example.com" required></div><div class="col-md-4"><button class="btn btn-outline-primary w-100" type="submit">Send test email</button></div></form></main></body></html>
