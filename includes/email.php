<?php

declare(strict_types=1);

function getMailConfig(): array
{
    return ['provider' => getSetting('email_provider', 'zoho'), 'host' => getSetting('smtp_host', 'smtp.zoho.com'), 'port' => (int) getSetting('smtp_port', '587'), 'encryption' => getSetting('smtp_encryption', 'tls'), 'username' => getSetting('smtp_username', ''), 'password' => getSetting('smtp_password', ''), 'from_name' => getSetting('smtp_from_name', APP_NAME), 'from_email' => getSetting('smtp_from_email', ''), 'site_url' => getSetting('site_url', 'http://localhost')];
}
function generateSecureToken(int $length = 32): string { return bin2hex(random_bytes(max(16, (int) ceil($length / 2)))); }

function sendSmtpMail(string $to, string $subject, string $body, array $config): bool
{
    $host = trim((string) ($config['host'] ?? '')); $port = (int) ($config['port'] ?? 587); $user = trim((string) ($config['username'] ?? '')); $pass = (string) ($config['password'] ?? ''); $from = trim((string) ($config['from_email'] ?? '')); $name = preg_replace('/[\r\n"]+/', '', (string) ($config['from_name'] ?? APP_NAME));
    if ($host === '' || $user === '' || $pass === '' || $from === '' || !filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) return false;
    $transport = strtolower((string) ($config['encryption'] ?? 'tls')) === 'ssl' ? 'ssl://' : 'tcp://';
    $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $error, 15, STREAM_CLIENT_CONNECT);
    if (!$socket) { error_log('SMTP connect failed: ' . $error); return false; }
    stream_set_timeout($socket, 15);
    $read = static function ($socket): string { $out = ''; while (($line = fgets($socket, 512)) !== false) { $out .= $line; if (isset($line[3]) && $line[3] === ' ') break; } return $out; };
    $write = static function ($socket, string $command) use ($read): bool { fwrite($socket, $command . "\r\n"); $response = $read($socket); return isset($response[0]) && in_array(substr($response, 0, 3), ['220','221','235','250','251','334','354'], true); };
    if (substr($read($socket), 0, 3) !== '220' || !$write($socket, 'EHLO localhost')) { fclose($socket); return false; }
    if (strtolower((string) ($config['encryption'] ?? 'tls')) === 'tls') { if (!$write($socket, 'STARTTLS') || !stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) || !$write($socket, 'EHLO localhost')) { fclose($socket); return false; } }
    if (!$write($socket, 'AUTH LOGIN') || !$write($socket, base64_encode($user)) || !$write($socket, base64_encode($pass)) || !$write($socket, 'MAIL FROM:<' . $from . '>') || !$write($socket, 'RCPT TO:<' . $to . '>') || !$write($socket, 'DATA')) { fclose($socket); return false; }
    $headers = 'From: ' . $name . ' <' . $from . ">\r\nTo: <" . $to . ">\r\nSubject: " . preg_replace('/[\r\n]+/', ' ', $subject) . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $body = chunk_split(base64_encode($body), 76, "\r\n");
    fwrite($socket, $headers . $body . "\r\n.\r\n"); $sent = substr($read($socket), 0, 3) === '250'; fwrite($socket, "QUIT\r\n"); fclose($socket); return $sent;
}
function sendEmail(string $to, string $subject, string $body): bool { $config = getMailConfig(); return sendSmtpMail($to, $subject, $body, $config); }
function sendUserEmailVerification(array $user): bool { $config = getMailConfig(); $token = generateSecureToken(); $pdo = Database::getInstance(); $stmt = $pdo->prepare('UPDATE users SET email_verification_token = :token, email_verification_expires_at = UTC_TIMESTAMP() + INTERVAL 24 HOUR WHERE id = :id'); $stmt->execute([':token' => $token, ':id' => (int) $user['id']]); $url = rtrim((string) $config['site_url'], '/') . '/verify-email.php?token=' . urlencode($token); return sendEmail((string) $user['email'], 'Verify your email address', '<p>Verify your account: <a href="' . e($url) . '">' . e($url) . '</a></p>'); }
function sendPasswordResetEmail(array $user): bool { $config = getMailConfig(); $token = generateSecureToken(); $pdo = Database::getInstance(); $stmt = $pdo->prepare('UPDATE users SET password_reset_token = :token, password_reset_expires_at = UTC_TIMESTAMP() + INTERVAL 1 HOUR WHERE id = :id'); $stmt->execute([':token' => $token, ':id' => (int) $user['id']]); $url = rtrim((string) $config['site_url'], '/') . '/reset-password.php?token=' . urlencode($token); return sendEmail((string) $user['email'], 'Reset your password', '<p>Reset your password: <a href="' . e($url) . '">' . e($url) . '</a></p>'); }
function sendAdminNewUserEmail(array $user): bool { $to = getSetting('admin_alert_email', ''); return $to === '' ? true : sendEmail($to, 'New user registered on ' . APP_NAME, '<p>Username: ' . e((string) $user['username']) . '</p><p>Email: ' . e((string) $user['email']) . '</p>'); }
function sendTwoFactorCodeEmail(array $user, string $code): bool { return sendEmail((string) $user['email'], 'Your two-factor login code', '<p>Your verification code is: <strong>' . e($code) . '</strong></p><p>It expires in 10 minutes.</p>'); }
