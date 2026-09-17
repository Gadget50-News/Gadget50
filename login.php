<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $twoFactorCode = trim((string) ($_POST['two_factor_code'] ?? ''));

    if (!empty($_SESSION['pending_2fa_user_id']) && !empty($_SESSION['pending_2fa_code'])) {
        if ($twoFactorCode === '' || !hash_equals((string) $_SESSION['pending_2fa_code'], $twoFactorCode)) {
            setFlash('danger', 'Invalid 2FA code. Please try again.');
            redirect('two-factor.php');
        }

        $userId = (int) $_SESSION['pending_2fa_user_id'];
        $stmt = Database::getInstance()->prepare('SELECT * FROM users WHERE id = :id AND status IN ("active", "inactive") LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            setFlash('danger', 'Session expired. Please log in again.');
            redirect('login.php');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = (string) $user['role'];
        unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code']);
        setFlash('success', 'Login successful.');
        redirect($user['role'] === 'super_admin' ? 'admin/index.php' : 'index.php');
    }

    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $pdo = Database::getInstance();

    $userStmt = $pdo->prepare('SELECT * FROM users WHERE (username = :identity OR email = :email) AND status IN ("active", "inactive") LIMIT 1');
    $userStmt->execute([':identity' => $identity, ':email' => $identity]);
    $user = $userStmt->fetch();

    if ($identity === '' || $password === '' || !$user || !password_verify($password, (string) $user['password_hash'])) {
        // Keep same brute-force behavior as before, but user must be verified before login is allowed.
        setFlash('danger', 'Invalid login credentials.');
        redirect('login.php');
    }

    if ((int) $user['email_verified'] !== 1) {
        setFlash('danger', 'Please verify your email address before logging in.');
        redirect('login.php');
    }

    if ((int) $user['two_factor_enabled'] === 1) {
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['pending_2fa_user_id'] = (int) $user['id'];
        $_SESSION['pending_2fa_code'] = $code;
        sendTwoFactorCodeEmail($user, $code);
        setFlash('success', 'A two-factor verification code has been sent to your email.');
        redirect('two-factor.php');
    }

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
    <title>Login | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="mb-3">Login</h2>
                        <?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
                        <form method="post" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <div class="mb-3">
                                <label class="form-label">Username or Email</label>
                                <input class="form-control" type="text" name="identity" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input class="form-control" type="password" name="password" required>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Login</button>
                        </form>
                        <div class="mt-3 text-center"><a href="forgot-password.php">Forgot password?</a></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
