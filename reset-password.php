<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $token = trim((string) ($_POST['token'] ?? '')); $password = (string) ($_POST['password'] ?? ''); $confirm = (string) ($_POST['password_confirmation'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $token) || strlen($password) < 12 || !hash_equals($password, $confirm)) { setFlash('danger', 'Invalid reset request or password.'); redirect('forgot-password.php'); }
    $pdo = Database::getInstance(); $stmt = $pdo->prepare('SELECT * FROM users WHERE password_reset_token = :token AND password_reset_expires_at > UTC_TIMESTAMP() LIMIT 1'); $stmt->execute([':token' => $token]); $user = $stmt->fetch();
    if (!$user) { setFlash('danger', 'This reset link is invalid or expired.'); redirect('forgot-password.php'); }
    $keys = ['ip:' . hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown')), 'device:' . hash('sha256', (string) ($_COOKIE['gadget50_device'] ?? '')), 'identity:' . hash('sha256', strtolower((string) $user['email']))]; $ph = implode(',', array_fill(0, count($keys), '?')); $block = $pdo->prepare("SELECT COUNT(*) FROM login_rate_limits WHERE rate_key IN ($ph) AND blocked_until > UTC_TIMESTAMP()"); $block->execute($keys);
    if ((int) $block->fetchColumn() > 0) { setFlash('danger', 'This account, device, or IP remains blocked for 48 hours. Password reset cannot bypass the block.'); redirect('login.php'); }
    $update = $pdo->prepare('UPDATE users SET password_hash = :hash, password_reset_token = NULL, password_reset_expires_at = NULL WHERE id = :id'); $update->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => (int) $user['id']]); setFlash('success', 'Your password was reset successfully.'); redirect('login.php');
}
$token = trim((string) ($_GET['token'] ?? '')); $flash = getFlash();
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reset Password | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><main class="container py-5"><div class="row justify-content-center"><div class="col-md-5"><div class="card border-0 shadow-sm"><div class="card-body p-4"><h2 class="mb-3">Reset Password</h2><?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="token" value="<?= e($token) ?>"><label class="form-label">New Password</label><input class="form-control mb-3" type="password" name="password" minlength="12" required><label class="form-label">Confirm Password</label><input class="form-control mb-3" type="password" name="password_confirmation" minlength="12" required><button class="btn btn-primary w-100">Update Password</button></form></div></div></div></div></main></body></html>
