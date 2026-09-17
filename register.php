<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        setFlash('danger', 'Use a valid username, email, and password of at least 8 characters.');
        redirect('register.php');
    }

    $pdo = Database::getInstance();
    $check = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
    $check->execute([':username' => $username, ':email' => $email]);
    if ($check->fetch()) {
        setFlash('danger', 'Username or email is already in use.');
        redirect('register.php');
    }

    $token = generateSecureToken();
    $expiresAt = date('Y-m-d H:i:s', time() + 86400);
    $userData = [
        'username' => $username,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'member',
        'status' => 'inactive',
        'email_verified' => 0,
        'email_verification_token' => $token,
        'email_verification_expires_at' => $expiresAt,
    ];

    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, status, email_verified, email_verification_token, email_verification_expires_at, created_at) VALUES (:username, :email, :password_hash, :role, :status, :email_verified, :email_verification_token, :email_verification_expires_at, NOW())');
    $stmt->execute([
        ':username' => $userData['username'],
        ':email' => $userData['email'],
        ':password_hash' => $userData['password_hash'],
        ':role' => $userData['role'],
        ':status' => $userData['status'],
        ':email_verified' => $userData['email_verified'],
        ':email_verification_token' => $userData['email_verification_token'],
        ':email_verification_expires_at' => $userData['email_verification_expires_at'],
    ]);

    $userId = (int) $pdo->lastInsertId();
    $user = ['id' => $userId, 'username' => $username, 'email' => $email];
    sendUserEmailVerification($user);
    sendAdminNewUserEmail($user);

    setFlash('success', 'Registration successful. Please check your email and verify your account before logging in.');
    redirect('login.php');
}

$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="mb-3">Create an account</h2>
                        <?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
                        <form method="post" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input class="form-control" type="text" name="username" required pattern="[A-Za-z0-9_.-]{3,80}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input class="form-control" type="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input class="form-control" type="password" name="password" minlength="8" required>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Register</button>
                        </form>
                        <div class="mt-3 text-center"><a href="login.php">Already have an account? Log in</a></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
