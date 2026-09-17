<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$token = trim((string) ($_GET['token'] ?? ''));
if (!preg_match('/^[a-f0-9]{64}$/', $token)) { http_response_code(400); exit('Invalid verification link.'); }
$pdo = Database::getInstance();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email_verification_token = :token AND email_verification_expires_at > UTC_TIMESTAMP() LIMIT 1');
$stmt->execute([':token' => $token]);
$userId = $stmt->fetchColumn();
if (!$userId) { http_response_code(400); exit('This verification link is invalid or expired.'); }
$update = $pdo->prepare('UPDATE users SET email_verified = 1, email_verification_token = NULL, email_verification_expires_at = NULL, status = CASE WHEN status = \'inactive\' THEN \'active\' ELSE status END WHERE id = :id');
$update->execute([':id' => (int) $userId]);
setFlash('success', 'Your email address has been verified. You can now log in.');
redirect('login.php');
