<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/email.php';
requireLogin('../login.php');
requireRole('super_admin', '../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verifyCsrf();
$to = trim((string) ($_POST['test_email'] ?? ''));
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    setFlash('danger', 'Enter a valid recipient email.');
} elseif (sendEmail($to, APP_NAME . ' SMTP test', '<p>SMTP test successful.</p>')) {
    setFlash('success', 'SMTP test email sent.');
} else {
    setFlash('danger', 'SMTP test failed. Check the mail settings and server logs.');
}
redirect('settings.php');
