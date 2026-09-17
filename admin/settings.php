<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$pdo = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['setting'] ?? [] as $key => $value) {
        $stmt = $pdo->prepare('UPDATE settings SET setting_value = :value, updated_at = NOW() WHERE setting_key = :key');
        $stmt->execute([':value' => (string) $value, ':key' => (string) $key]);
    }
    setFlash('success', 'Site settings updated successfully.');
    redirect('settings.php');
}

$settings = $pdo->query('SELECT setting_key, setting_value FROM settings ORDER BY setting_key')->fetchAll();
$settingsMap = [];
foreach ($settings as $row) {
    $settingsMap[$row['setting_key']] = $row['setting_value'];
}
$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Site Settings</h2>
            <a href="index.php" class="btn btn-outline-secondary">Back to dashboard</a>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Website Name</label>
                            <input type="text" name="setting[site_name]" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['site_name'] ?? 'Gadget 50'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Breaking News</label>
                            <input type="text" name="setting[breaking_news]" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['breaking_news'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Header Text</label>
                            <input type="text" name="setting[header_text]" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['header_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Footer Copyright</label>
                            <input type="text" name="setting[footer_copyright]" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['footer_copyright'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Logo URL</label>
                            <input type="text" name="setting[site_logo]" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['site_logo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Favicon URL</label>
                            <input type="text" name="setting[site_favicon]" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['site_favicon'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-4">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
