<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => (string) ($params['path'] ?? '/'),
            'domain' => (string) ($params['domain'] ?? ''),
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => (bool) ($params['httponly'] ?? true),
            'samesite' => (string) ($params['samesite'] ?? 'Lax'),
        ]);
    }

    session_destroy();
    header('Location: ' . appUrl('index.php'), true, 302);
    exit;
}

$siteName = getSetting('site_name', APP_NAME);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign out | <?= e($siteName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5"><div class="row justify-content-center"><div class="col-md-5"><div class="card border-0 shadow-sm"><div class="card-body p-4 text-center">
    <h2 class="mb-3">Sign out?</h2>
    <p class="text-muted">Are you sure you want to end your session?</p>
    <form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><button type="submit" class="btn btn-danger">Sign out</button></form>
    <a href="<?= e(appUrl('index.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
</div></div></div></div></main>
</body>
</html>
