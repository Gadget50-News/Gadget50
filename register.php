<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email.php';

if (isLoggedIn()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
        setFlash('danger', 'Use a valid username, email, and password of at least 12 characters.'); redirect('register.php');
    }
    $pdo = Database::getInstance();
    $check = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
    $check->execute([':username' => $username, ':email' => $email]);
    if ($check->fetch()) { setFlash('danger', 'Username or email is already in use.'); redirect('register.php'); }

    $emailAvailable = emailServiceAvailable();
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO users (username,email,email_verified,password_hash,role,status,created_at) VALUES (:username,:email,:verified,:password_hash,:role,:status,NOW())');
        $stmt->execute([':username'=>$username, ':email'=>$email, ':verified'=>$emailAvailable ? 0 : 1, ':password_hash'=>password_hash($password, PASSWORD_DEFAULT), ':role'=>'member', ':status'=>$emailAvailable ? 'inactive' : 'active']);
        $user = ['id'=>(int) $pdo->lastInsertId(), 'username'=>$username, 'email'=>$email];
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack(); error_log('Registration failed: ' . $e->getMessage()); setFlash('danger', 'Registration could not be completed. Please try again.'); redirect('register.php');
    }
    if ($emailAvailable) {
        $sent = sendUserEmailVerification($user); sendAdminNewUserEmail($user);
        setFlash($sent ? 'success' : 'danger', $sent ? 'Registration successful. Please verify your email before logging in.' : 'Account created, but the verification email could not be sent.');
    } else {
        setFlash('success', 'Registration successful. Email verification is unavailable until the administrator activates Email Service.');
    }
    redirect('login.php');
}
$flash = getFlash();
?><!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Register | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><main class="container py-5"><div class="row justify-content-center"><div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body p-4"><h2 class="mb-3">Create an account</h2><?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?><form method="post" autocomplete="off"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required pattern="[A-Za-z0-9_.-]{3,80}"></div><div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div><div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" minlength="12" required></div><button class="btn btn-primary w-100">Register</button></form><div class="mt-3 text-center"><a href="<?= e(appUrl('login.php')) ?>">Already have an account? Log in</a></div></div></div></div></div></main></body></html>
