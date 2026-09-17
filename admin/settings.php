<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if (empty($_SESSION['pending_2fa_user_id']) || empty($_SESSION['pending_2fa_code'])) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $code = trim((string) ($_POST['two_factor_code'] ?? ''));
    if ($code === '' || !hash_equals((string) $_SESSION['pending_2fa_code'], $code)) {
        setFlash('danger', 'Invalid 2FA code. Please try again.');
        redirect('two-factor.php');
    }

    $userId = (int) $_SESSION['pending_2fa_user_id'];
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id AND status = :status LIMIT 1');
    $stmt->execute([':id' => $userId, ':status' => 'active']);
    $user = $stmt->fetch();

    if (!$user) {
        setFlash('danger', 'Session expired. Please log in again.');
        redirect('login.php');
    }

    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code']);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_role'] = (string) $user['role'];
    setFlash('success', 'Login successful.');
    redirect($user['role'] === 'super_admin' ? 'admin/index.php' : 'index.php');
}

$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="mb-3">Two-Factor Verification</h2>
                        <?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
                        <p class="text-muted">A six-digit code was sent to your email address.</p>
                        <form method="post" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <div class="mb-3">
                                <label class="form-label">Verification Code</label>
                                <input class="form-control" type="text" name="two_factor_code" inputmode="numeric" maxlength="6" required>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Verify</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
