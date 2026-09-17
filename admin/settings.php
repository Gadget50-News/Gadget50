<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$pdo = Database::getInstance();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach ($_POST['setting'] ?? [] as $key => $value) {
        $allowed = ['site_name', 'site_logo', 'site_favicon', 'header_text', 'footer_copyright', 'breaking_news'];
        if (in_array((string) $key, $allowed, true)) {
            $stmt = $pdo->prepare('UPDATE settings SET setting_value = :value, updated_at = NOW() WHERE setting_key = :key');
            $stmt->execute([':value' => trim((string) $value), ':key' => (string) $key]);
        }
    }
    setFlash('success', 'Site settings updated successfully.');
    redirect('settings.php');
}
$settings = $pdo->query('SELECT setting_key, setting_value FROM settings ORDER BY setting_key')->fetchAll();
$settingsMap = [];
foreach ($settings as $row) { $settingsMap[$row['setting_key']] = $row['setting_value']; }
$flash = getFlash();
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Site Settings | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-5"><div class="d-flex justify-content-between align-items-center mb-4"><h2>Site Settings</h2><a href="index.php" class="btn btn-outline-secondary">Back to dashboard</a></div><?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?><div class="card shadow-sm border-0"><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="row g-3"><?php foreach (['site_name' => 'Website Name', 'breaking_news' => 'Breaking News', 'header_text' => 'Header Text', 'footer_copyright' => 'Footer Copyright', 'site_logo' => 'Logo URL', 'site_favicon' => 'Favicon URL'] as $key => $label): ?><div class="col-md-6"><label class="form-label"><?= e($label) ?></label><input type="text" name="setting[<?= e($key) ?>]" class="form-control" value="<?= e((string) ($settingsMap[$key] ?? '')) ?>"></div><?php endforeach; ?></div><button type="submit" class="btn btn-primary mt-4">Save Settings</button></form></div></div></div></body></html>
