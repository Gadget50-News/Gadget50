<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$pdo = Database::getInstance();
$genericLoginError = 'Invalid login credentials.';
$blockedMessage = 'Too many failed login attempts. Please try again after 48 hours.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $ipKey = 'ip:' . hash('sha256', $ip);
    $identityKey = 'identity:' . hash('sha256', strtolower($identity));

    try {
        $rateStmt = $pdo->prepare('SELECT rate_key, failed_attempts, blocked_until FROM login_rate_limits WHERE rate_key IN (:ip_key, :identity_key)');
        $rateStmt->execute([':ip_key' => $ipKey, ':identity_key' => $identityKey]);
        $limits = [];
        foreach ($rateStmt->fetchAll() as $limit) {
            $limits[(string) $limit['rate_key']] = $limit;
        }

        $now = time();
        foreach ([$ipKey, $identityKey] as $rateKey) {
            $blockedUntil = (int) ($limits[$rateKey]['blocked_until'] ?? 0);
            if ($blockedUntil > $now) {
                setFlash('danger', $blockedMessage);
                redirect('login.php');
            }
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = :identity OR email = :email) AND status = :status LIMIT 1');
        $stmt->execute([
            ':identity' => $identity,
            ':email' => $identity,
            ':status' => 'active',
        ]);
        $user = $stmt->fetch();

        if ($identity === '' || $password === '' || !$user || !password_verify($password, (string) $user['password_hash'])) {
            $pdo->beginTransaction();
            $upsert = $pdo->prepare('INSERT INTO login_rate_limits (rate_key, failed_attempts, blocked_until, last_failed_at) VALUES (:rate_key, 1, NULL, NOW()) ON DUPLICATE KEY UPDATE failed_attempts = failed_attempts + 1, blocked_until = CASE WHEN failed_attempts + 1 >= 5 THEN DATE_ADD(NOW(), INTERVAL 48 HOUR) ELSE blocked_until END, last_failed_at = NOW()');
            $upsert->execute([':rate_key' => $ipKey]);
            $upsert->execute([':rate_key' => $identityKey]);
            $pdo->commit();

            $blocked = false;
            foreach ([$ipKey, $identityKey] as $rateKey) {
                $check = $pdo->prepare('SELECT blocked_until FROM login_rate_limits WHERE rate_key = :rate_key LIMIT 1');
                $check->execute([':rate_key' => $rateKey]);
                $blockedUntil = strtotime((string) ($check->fetchColumn() ?: ''));
                if ($blockedUntil !== false && $blockedUntil > time()) {
                    $blocked = true;
                    break;
                }
            }

            setFlash('danger', $blocked ? $blockedMessage : $genericLoginError);
            redirect('login.php');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = (string) $user['role'];

        $clear = $pdo->prepare('DELETE FROM login_rate_limits WHERE rate_key IN (:ip_key, :identity_key)');
        $clear->execute([':ip_key' => $ipKey, ':identity_key' => $identityKey]);

        setFlash('success', 'Login successful.');
        redirect($user['role'] === 'super_admin' ? 'admin/index.php' : 'index.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Login processing failed: ' . $e->getMessage());
        setFlash('danger', $genericLoginError);
        redirect('login.php');
    }
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
                        <?php if ($flash): ?>
                            <div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
                        <?php endif; ?>
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
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
