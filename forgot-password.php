<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $identity = trim((string) ($_POST['identity'] ?? ''));
    $pdo = Database::getInstance();
    $user = null;

    if ($identity !== '') {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :identity OR email = :email LIMIT 1');
        $stmt->execute([':identity' => $identity, ':email' => $identity]);
        $user = $stmt->fetch();
    }

    if (!$user) {
        setFlash('danger', 'If that account exists, a password reset email will be sent.');
        redirect('forgot-password.php');
    }

    sendPasswordResetEmail($user);
    setFlash('success', 'If that account exists, a password reset email has been sent.');
    redirect('forgot-password.php');
}

$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="mb-3">Forgot Password</h2>
                        <?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <div class="mb-3">
                                <label class="form-label">Username or Email</label>
                                <input class="form-control" type="text" name="identity" required>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Send reset link</button>
                        </form>
                        <div class="mt-3 text-center"><a href="login.php">Back to login</a></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
