<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email.php';

if (isLoggedIn()) redirect('index.php');

$pdo = Database::getInstance();
$maxAttempts = 5;
$generic = 'Invalid login credentials.';
$blocked = 'Too many failed attempts. This IP address and device are blocked for 48 hours.';
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$device = (string) ($_COOKIE['gadget50_device'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $device)) {
    $device = bin2hex(random_bytes(32));
    setcookie('gadget50_device', $device, ['expires' => time() + 31536000, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
}
$key = static fn (string $scope, string $value): string => $scope . ':' . hash('sha256', $value);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $keys = [$key('ip', $ip), $key('device', $device), $key('identity', strtolower($identity))];
    $placeholders = implode(',', array_fill(0, count($keys), '?'));

    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare("SELECT blocked_until FROM login_rate_limits WHERE rate_key IN ($placeholders) FOR UPDATE");
        $lock->execute($keys);
        foreach ($lock->fetchAll() as $row) {
            if ($row['blocked_until'] !== null && strtotime((string) $row['blocked_until']) > time()) {
                $pdo->rollBack(); setFlash('danger', $blocked); redirect('login.php');
            }
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :identity OR email = :email LIMIT 1');
        $stmt->execute([':identity' => $identity, ':email' => $identity]);
        $user = $stmt->fetch();
        $valid = $identity !== '' && $password !== '' && $user && (string) $user['status'] === 'active' && (int) ($user['email_verified'] ?? 1) === 1 && password_verify($password, (string) $user['password_hash']);

        if (!$valid) {
            foreach ($keys as $rateKey) {
                $upsert = $pdo->prepare('INSERT INTO login_rate_limits (rate_key, failed_attempts, blocked_until, last_failed_at) VALUES (?, 1, NULL, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE failed_attempts = failed_attempts + 1, blocked_until = CASE WHEN failed_attempts + 1 >= 5 THEN DATE_ADD(UTC_TIMESTAMP(), INTERVAL 48 HOUR) ELSE blocked_until END, last_failed_at = UTC_TIMESTAMP()');
                $upsert->execute([$rateKey]);
            }
            $count = $pdo->prepare("SELECT COALESCE(MAX(failed_attempts), 0) FROM login_rate_limits WHERE rate_key IN ($placeholders)");
            $count->execute($keys);
            $attempts = (int) $count->fetchColumn();
            $pdo->commit();
            if ($attempts >= $maxAttempts) setFlash('danger', $blocked);
            elseif ($attempts === 4) setFlash('danger', 'Warning: one more failed attempt will block this IP and device for 48 hours.');
            elseif ($attempts >= 2) setFlash('danger', sprintf('Invalid credentials. %d failed attempts; %d attempts remaining.', $attempts, $maxAttempts - $attempts));
            else setFlash('danger', $generic);
            redirect('login.php');
        }

        $clear = $pdo->prepare("DELETE FROM login_rate_limits WHERE rate_key IN ($placeholders)");
        $clear->execute($keys);
        if ((int) ($user['two_factor_enabled'] ?? 0) === 1) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['pending_2fa_user_id'] = (int) $user['id'];
            $_SESSION['pending_2fa_hash'] = hash('sha256', $code);
            $_SESSION['pending_2fa_expires'] = time() + 600;
            $pdo->commit();
            if (!sendTwoFactorCodeEmail($user, $code)) { unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_hash'], $_SESSION['pending_2fa_expires']); setFlash('danger', 'Unable to send the verification code.'); redirect('login.php'); }
            setFlash('success', 'A verification code was sent to your email.'); redirect('two-factor.php');
        }
        $pdo->commit();
        session_regenerate_id(true); $_SESSION['user_id'] = (int) $user['id']; $_SESSION['user_role'] = (string) $user['role'];
        setFlash('success', 'Login successful.'); redirect($user['role'] === 'super_admin' ? 'admin/index.php' : 'index.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Login error: ' . $e->getMessage()); setFlash('danger', $generic); redirect('login.php');
    }
}

$flash = getFlash();
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5"><div class="row justify-content-center"><div class="col-md-5"><div class="card border-0 shadow-sm"><div class="card-body p-4"><h2 class="mb-3">Login</h2><?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?><form method="post" autocomplete="off"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="mb-3"><label class="form-label">Username or Email</label><input class="form-control" name="identity" required></div><div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required></div><button class="btn btn-primary w-100">Login</button></form><div class="mt-3 text-center"><a href="forgot-password.php">Forgot password?</a></div></div></div></div></div></main></body></html>
