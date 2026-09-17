<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$pdo = Database::getInstance();
$maxAttempts = 5;
$blockSeconds = 48 * 60 * 60;
$genericMessage = 'Invalid login credentials.';
$blockedMessage = 'Too many failed login attempts. This IP address and device are blocked for 48 hours.';

$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$deviceToken = (string) ($_COOKIE['gadget50_device'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $deviceToken)) {
    $deviceToken = bin2hex(random_bytes(32));
    setcookie('gadget50_device', $deviceToken, [
        'expires' => time() + (365 * 86400),
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

$makeKey = static function (string $scope, string $value): string {
    return $scope . ':' . hash('sha256', $value);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    // REMOTE_ADDR is intentionally used; forwarded headers are client-controlled unless
    // a trusted reverse proxy has been explicitly configured at the server level.
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    $keys = [
        $makeKey('ip', $ip),
        $makeKey('device', $deviceToken),
        $makeKey('identity', strtolower($identity)),
    ];

    try {
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $pdo->beginTransaction();

        // Lockout is checked before password verification: a correct password cannot
        // bypass an active IP, device, or identity lock.
        $lockStmt = $pdo->prepare("SELECT rate_key, failed_attempts, blocked_until FROM login_rate_limits WHERE rate_key IN ($placeholders) FOR UPDATE");
        $lockStmt->execute($keys);
        $limits = $lockStmt->fetchAll();
        $highestAttempts = 0;
        foreach ($limits as $limit) {
            $highestAttempts = max($highestAttempts, (int) $limit['failed_attempts']);
            if ($limit['blocked_until'] !== null && strtotime((string) $limit['blocked_until']) > time()) {
                $pdo->rollBack();
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
        $validCredentials = $identity !== '' && $password !== '' && $user && password_verify($password, (string) $user['password_hash']);

        if (!$validCredentials) {
            foreach ($keys as $key) {
                $upsert = $pdo->prepare('INSERT INTO login_rate_limits (rate_key, failed_attempts, blocked_until, last_failed_at) VALUES (?, 1, NULL, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE failed_attempts = LEAST(failed_attempts + 1, 4294967295), blocked_until = CASE WHEN failed_attempts + 1 >= ? THEN DATE_ADD(UTC_TIMESTAMP(), INTERVAL 48 HOUR) ELSE blocked_until END, last_failed_at = UTC_TIMESTAMP()');
                $upsert->execute([$key, $maxAttempts]);
            }

            $countStmt = $pdo->prepare("SELECT COALESCE(MAX(failed_attempts), 0) FROM login_rate_limits WHERE rate_key IN ($placeholders)");
            $countStmt->execute($keys);
            $attempts = (int) $countStmt->fetchColumn();
            $pdo->commit();

            // Progressive delay slows automated attacks without changing the design.
            usleep(min(5000000, max(250000, $attempts * 500000)));

            if ($attempts >= $maxAttempts) {
                setFlash('danger', $blockedMessage);
            } elseif ($attempts === 4) {
                setFlash('danger', 'সতর্কতা: এটি আপনার শেষ সুযোগ। আর একবার ভুল পাসওয়ার্ড দিলে এই IP ও ডিভাইস ৪৮ ঘণ্টার জন্য ব্লক হবে।');
            } elseif ($attempts >= 2) {
                $remaining = $maxAttempts - $attempts;
                setFlash('danger', sprintf('ভুল পাসওয়ার্ড। আপনি %d বার ভুল করেছেন; আর সর্বোচ্চ %d বার চেষ্টা করতে পারবেন।', $attempts, $remaining));
            } else {
                setFlash('danger', $genericMessage);
            }
            redirect('login.php');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = (string) $user['role'];
        $clearStmt = $pdo->prepare("DELETE FROM login_rate_limits WHERE rate_key IN ($placeholders)");
        $clearStmt->execute($keys);
        $pdo->commit();

        setFlash('success', 'Login successful.');
        redirect($user['role'] === 'super_admin' ? 'admin/index.php' : 'index.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Login processing failed: ' . $e->getMessage());
        setFlash('danger', $genericMessage);
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
