<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$pdo = Database::getInstance();
$genericLoginError = 'Invalid login credentials.';
$blockedMessage = 'Too many failed login attempts. This device and IP address are blocked for 48 hours.';
$maxAttempts = 5;
$blockHours = 48;

$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$deviceToken = (string) ($_COOKIE['gadget50_device'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $deviceToken)) {
    $deviceToken = bin2hex(random_bytes(32));
    setcookie('gadget50_device', $deviceToken, [
        'expires' => time() + (86400 * 365),
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function loginRateKey(string $type, string $value): string
{
    return $type . ':' . hash('sha256', $value);
}

function getLoginBlock(PDO $pdo, string $ipKey, string $deviceKey): ?array
{
    $stmt = $pdo->prepare('SELECT rate_key, failed_attempts, blocked_until FROM login_rate_limits WHERE rate_key = :ip_key OR rate_key = :device_key');
    $stmt->execute([':ip_key' => $ipKey, ':device_key' => $deviceKey]);
    $now = time();
    $highest = null;

    foreach ($stmt->fetchAll() as $row) {
        $blockedUntil = $row['blocked_until'] !== null ? strtotime((string) $row['blocked_until']) : false;
        if ($blockedUntil !== false && $blockedUntil > $now) {
            return [
                'blocked' => true,
                'failed_attempts' => (int) $row['failed_attempts'],
                'blocked_until' => $blockedUntil,
            ];
        }

        if ($highest === null || (int) $row['failed_attempts'] > (int) $highest['failed_attempts']) {
            $highest = [
                'blocked' => false,
                'failed_attempts' => (int) $row['failed_attempts'],
                'blocked_until' => null,
            ];
        }
    }

    return $highest;
}

function recordLoginFailure(PDO $pdo, string $ipKey, string $deviceKey, int $maxAttempts, int $blockHours): int
{
    $upsert = $pdo->prepare('INSERT INTO login_rate_limits (rate_key, failed_attempts, blocked_until, last_failed_at) VALUES (:rate_key, 1, NULL, NOW()) ON DUPLICATE KEY UPDATE failed_attempts = failed_attempts + 1, blocked_until = CASE WHEN failed_attempts + 1 >= :max_attempts THEN DATE_ADD(NOW(), INTERVAL 48 HOUR) ELSE blocked_until END, last_failed_at = NOW()');

    foreach ([$ipKey, $deviceKey] as $rateKey) {
        $upsert->execute([
            ':rate_key' => $rateKey,
            ':max_attempts' => $maxAttempts,
        ]);
    }

    $stmt = $pdo->prepare('SELECT COALESCE(MAX(failed_attempts), 0) FROM login_rate_limits WHERE rate_key = :ip_key OR rate_key = :device_key');
    $stmt->execute([':ip_key' => $ipKey, ':device_key' => $deviceKey]);
    return (int) $stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $ipKey = loginRateKey('ip', $ip);
    $deviceKey = loginRateKey('device', $deviceToken);

    try {
        // A block is checked before credential verification: correct credentials cannot bypass it.
        $existingBlock = getLoginBlock($pdo, $ipKey, $deviceKey);
        if ($existingBlock !== null && $existingBlock['blocked'] === true) {
            setFlash('danger', $blockedMessage);
            redirect('login.php');
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
            $attempts = recordLoginFailure($pdo, $ipKey, $deviceKey, $maxAttempts, $blockHours);
            $pdo->commit();

            if ($attempts >= $maxAttempts) {
                setFlash('danger', $blockedMessage);
                redirect('login.php');
            }

            $remaining = $maxAttempts - $attempts;
            if ($attempts >= 2) {
                $message = sprintf('ভুল পাসওয়ার্ড। আপনি %d বার ভুল করেছেন; আর সর্বোচ্চ %d বার চেষ্টা করতে পারবেন।', $attempts, $remaining);
                if ($remaining === 1) {
                    $message = 'সতর্কতা: এটি আপনার শেষ সুযোগ। আর একবার ভুল পাসওয়ার্ড দিলে এই ডিভাইস ও IP ঠিকানা ৪৮ ঘণ্টার জন্য ব্লক হবে।';
                }
            } else {
                $message = $genericLoginError;
            }

            setFlash('danger', $message);
            redirect('login.php');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = (string) $user['role'];

        $clear = $pdo->prepare('DELETE FROM login_rate_limits WHERE rate_key = :ip_key OR rate_key = :device_key');
        $clear->execute([':ip_key' => $ipKey, ':device_key' => $deviceKey]);

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
