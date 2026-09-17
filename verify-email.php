<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $token = trim((string) ($_POST['token'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirmation'] ?? '');

    if ($token === '' || strlen($password) < 8 || $password !== $confirm) {
        setFlash('danger', 'Invalid reset request.');
        redirect('reset-password.php?token=' . urlencode($token));
    }

    $pdo = Database::getInstance();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE password_reset_token = :token AND password_reset_expires_at > NOW() LIMIT 1');
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        setFlash('danger', 'This password reset link is invalid or expired.');
        redirect('login.php');
    }

    $update = $pdo->prepare('UPDATE users SET password_hash = :hash, password_reset_token = NULL, password_reset_expires_at = NULL WHERE id = :id');
    $update->execute([
        ':hash' => password_hash($password, PASSWORD_DEFAULT),
        ':id' => (int) $user['id'],
    ]);

    setFlash('success', 'Your password was reset successfully.');
    redirect('login.php');
}

$token = trim((string) ($_GET['token'] ?? ''));
$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Password Reset | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="mb-3">Set New Password</h2>
                        <?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="token" value="<?= e($token) ?>">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input class="form-control" type="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input class="form-control" type="password" name="password_confirmation" required>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Save New Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
