<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$pdo = Database::getInstance();
$genericMessage = 'Invalid login credentials.';
$blockedMessage = 'Too many failed login attempts. This IP address and device are blocked for 48 hours.';
$maxAttempts = 5;

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
    $twoFactorCode = trim((string) ($_POST['two_factor_code'] ?? ''));
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $keys = [
        $makeKey('ip', $ip),
        $makeKey('device', $deviceToken),
        $makeKey('identity', strtolower($identity)),
    ];

    try {
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $pdo->beginTransaction();

        $lockStmt = $pdo->prepare("SELECT rate_key, failed_attempts, blocked_until FROM login_rate_limits WHERE rate_key IN ($placeholders) FOR UPDATE");
        $lockStmt->execute($keys);
        foreach ($lockStmt->fetchAll() as $limit) {
            if ($limit['blocked_until'] !== null && strtotime((string) $limit['blocked_until']) > time()) {
                $pdo->rollBack();
                setFlash('danger', $blockedMessage);
                redirect('login.php');
            }
        }

        if (!empty($_SESSION['pending_2fa_user_id']) && !empty($_SESSION['pending_2fa_code'])) {
            if ($twoFactorCode === '' || !hash_equals((string) $_SESSION['pending_2fa_code'], $twoFactorCode)) {
                $pdo->rollBack();
                setFlash('danger', 'Invalid 2FA code. Please try again.');
                redirect('two-factor.php');
            }

            $userId = (int) $_SESSION['pending_2fa_user_id'];
            $userStmt = $pdo->prepare('SELECT * FROM users WHERE id = :id AND status = :status LIMIT 1');
            $userStmt->execute([':id' => $userId, ':status' => 'active']);
            $user = $userStmt->fetch();
            if (!$user) {
                $pdo->rollBack();
                setFlash('danger', 'Session expired. Please log in again.');
                redirect('login.php');
            }

            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_code']);
            $clearStmt = $pdo->prepare("DELETE FROM login_rate_limits WHERE rate_key IN ($placeholders)");
            $clearStmt->execute($keys);
            $pdo->commit();

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_role'] = (string) $user['role'];
            setFlash('success', 'Login successful.');
            redirect($user['role'] === 'super_admin' ? 'admin/index.php' : 'index.php');
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = :identity OR email = :email) AND status IN ("active", "inactive") LIMIT 1');
        $stmt->execute([':identity' => $identity, ':email' => $identity]);
        $user = $stmt->fetch();

        if (($identity === '' || $password === '') || !$user || !password_verify($password, (string) $user['password_hash'])) {
            foreach ($keys as $key) {
                $upsert = $pdo->prepare('INSERT INTO login_rate_limits (rate_key, failed_attempts, blocked_until, last_failed_at) VALUES (:rate_key, 1, NULL, NOW()) ON DUPLICATE KEY UPDATE failed_attempts = failed_attempts + 1, blocked_until = CASE WHEN failed_attempts + 1 >= :max THEN DATE_ADD(NOW(), INTERVAL 48 HOUR) ELSE blocked_until END, last_failed_at = NOW()');
                $upsert->execute([':rate_key' => $key, ':max' => $maxAttempts]);
            }

            $countStmt = $pdo->prepare("SELECT COALESCE(MAX(failed_attempts), 0) FROM login_rate_limits WHERE rate_key IN ($placeholders)");
            $countStmt->execute($keys);
            $attempts = (int) $countStmt->fetchColumn();
            $pdo->commit();

            if ($attempts >= $maxAttempts) {
                setFlash('danger', $blockedMessage);
                redirect('login.php');
            }

            if ($attempts >= 2) {
                $remaining = max(1, $maxAttempts - $attempts);
                if ($attempts === 4) {
                    $message = 'সতর্কতা: এটি আপনার শেষ সুযোগ। আর একবার ভুল পাসওয়ার্ড দিলে এই IP ও ডিভাইস ৪৮ ঘণ্টার জন্য ব্লক হবে।';
                } else {
                    $message = sprintf('ভুল পাসওয়ার্ড। আপনি %d বার ভুল করেছেন; আর সর্বোচ্চ %d বার চেষ্টা করতে পারবেন।', $attempts, $remaining);
                }
            } else {
                $message = $genericMessage;
            }

            setFlash('danger', $message);
            redirect('login.php');
        }

        if ((int) $user['email_verified'] !== 1) {
            $pdo->rollBack();
            setFlash('danger', 'Please verify your email address before logging in.');
            redirect('login.php');
        }

        if ((int) $user['two_factor_enabled'] === 1) {
            $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['pending_2fa_user_id'] = (int) $user['id'];
            $_SESSION['pending_2fa_code'] = $code;
            $clearStmt = $pdo->prepare("DELETE FROM login_rate_limits WHERE rate_key IN ($placeholders)");
            $clearStmt->execute($keys);
            $pdo->commit();
            sendTwoFactorCodeEmail($user, $code);
            setFlash('success', 'A two-factor verification code has been sent to your email.');
            redirect('two-factor.php');
        }

        $clearStmt = $pdo->prepare("DELETE FROM login_rate_limits WHERE rate_key IN ($placeholders)");
        $clearStmt->execute($keys);
        $pdo->commit();

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = (string) $user['role'];
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
