<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (empty($_SESSION['pending_2fa_user_id']) || empty($_SESSION['pending_2fa_hash']) || time() > (int) ($_SESSION['pending_2fa_expires'] ?? 0)) {
        unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_hash'], $_SESSION['pending_2fa_expires']); setFlash('danger', 'The verification session expired.'); redirect('login.php');
    }
    $code = trim((string) ($_POST['two_factor_code'] ?? ''));
    if (!preg_match('/^\d{6}$/', $code) || !hash_equals((string) $_SESSION['pending_2fa_hash'], hash('sha256', $code))) { setFlash('danger', 'Invalid verification code.'); redirect('two-factor.php'); }
    $stmt = Database::getInstance()->prepare('SELECT id, role FROM users WHERE id = :id AND status = :status AND email_verified = 1 LIMIT 1'); $stmt->execute([':id' => (int) $_SESSION['pending_2fa_user_id'], ':status' => 'active']); $user = $stmt->fetch();
    if (!$user) { unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_hash'], $_SESSION['pending_2fa_expires']); setFlash('danger', 'Login session expired.'); redirect('login.php'); }
    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_hash'], $_SESSION['pending_2fa_expires']); session_regenerate_id(true); $_SESSION['user_id'] = (int) $user['id']; $_SESSION['user_role'] = (string) $user['role']; setFlash('success', 'Login successful.'); redirect($user['role'] === 'super_admin' ? 'admin/index.php' : 'index.php');
}
$flash = getFlash();
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Two-Factor Verification | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><main class="container py-5"><div class="row justify-content-center"><div class="col-md-5"><div class="card border-0 shadow-sm"><div class="card-body p-4"><h2 class="mb-3">Two-Factor Verification</h2><?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?><p class="text-muted">Enter the six-digit code sent to your email.</p><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input class="form-control mb-3" name="two_factor_code" inputmode="numeric" maxlength="6" required><button class="btn btn-primary w-100">Verify</button></form></div></div></div></div></main></body></html>
