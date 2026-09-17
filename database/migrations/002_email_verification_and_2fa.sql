<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$token = trim((string) ($_GET['token'] ?? ''));
if ($token === '') {
    http_response_code(400);
    exit('Invalid verification token.');
}

$pdo = Database::getInstance();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email_verification_token = :token AND email_verification_expires_at > NOW() LIMIT 1');
$stmt->execute([':token' => $token]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(400);
    exit('This verification link is invalid or expired.');
}

$update = $pdo->prepare('UPDATE users SET email_verified = 1, email_verification_token = NULL, email_verification_expires_at = NULL, status = :status WHERE id = :id');
$update->execute([':status' => 'active', ':id' => (int) $user['id']]);

setFlash('success', 'Your email address has been verified successfully.');
redirect('login.php');
