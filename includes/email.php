<?php
declare(strict_types=1);

function mailDefaults(string $provider): array
{
    return match (strtolower($provider)) {
        'gmail' => ['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls'],
        'zoho' => ['host' => 'smtp.zoho.com', 'port' => 587, 'encryption' => 'tls'],
        default => ['host' => '', 'port' => 587, 'encryption' => 'tls'],
    };
}

function getMailConfig(): array
{
    $provider = getSetting('email_provider', '');
    $defaults = mailDefaults($provider);
    return [
        'provider' => $provider,
        'enabled' => getSetting('email_service_enabled', '0') === '1',
        'validated' => getSetting('email_smtp_validated', '0') === '1',
        'host' => getSetting('smtp_host', $defaults['host']),
        'port' => (int) getSetting('smtp_port', (string) $defaults['port']),
        'encryption' => getSetting('smtp_encryption', $defaults['encryption']),
        'username' => getSetting('smtp_username', ''),
        'password' => getSetting('smtp_password', ''),
        'from_email' => getSetting('smtp_from_email', ''),
        'from_name' => getSetting('smtp_from_name', defined('APP_NAME') ? APP_NAME : 'Gadget 50'),
    ];
}

function emailServiceAvailable(): bool
{
    $config = getMailConfig();
    return $config['enabled'] && $config['validated'];
}

function sendSmtpMail(string $to, string $subject, string $body, array $config): bool
{
    $host = trim((string) ($config['host'] ?? ''));
    $port = (int) ($config['port'] ?? 587);
    $username = trim((string) ($config['username'] ?? ''));
    $password = (string) ($config['password'] ?? '');
    $from = trim((string) ($config['from_email'] ?? ''));
    $encryption = strtolower((string) ($config['encryption'] ?? 'tls'));

    if ($host === '' || $port < 1 || $port > 65535 || $username === '' || $password === '' ||
        !filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL) ||
        !in_array($encryption, ['tls', 'ssl', 'none'], true)) return false;

    $transport = $encryption === 'ssl' ? 'ssl://' : 'tcp://';
    $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $error, 15, STREAM_CLIENT_CONNECT);
    if (!$socket) { error_log('SMTP connection failed: ' . (string) $errno); return false; }
    stream_set_timeout($socket, 15);
    $read = static function ($socket): string {
        $result = '';
        while (($line = fgets($socket, 512)) !== false) { $result .= $line; if (isset($line[3]) && $line[3] === ' ') break; }
        return $result;
    };
    $send = static function ($socket, string $command) use ($read): string { fwrite($socket, $command . "\r\n"); return $read($socket); };
    $is = static fn (string $response, array $codes): bool => in_array(substr($response, 0, 3), $codes, true);

    if (!$is($read($socket), ['220']) || !$is($send($socket, 'EHLO localhost'), ['250'])) { fclose($socket); return false; }
    if ($encryption === 'tls' && (!$is($send($socket, 'STARTTLS'), ['220']) || !stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) || !$is($send($socket, 'EHLO localhost'), ['250']))) { fclose($socket); return false; }
    if (!$is($send($socket, 'AUTH LOGIN'), ['334']) || !$is($send($socket, base64_encode($username)), ['334']) || !$is($send($socket, base64_encode($password)), ['235'])) { fclose($socket); return false; }
    if (!$is($send($socket, 'MAIL FROM:<' . $from . '>'), ['250']) || !$is($send($socket, 'RCPT TO:<' . $to . '>'), ['250', '251']) || !$is($send($socket, 'DATA'), ['354'])) { fclose($socket); return false; }

    $safeSubject = preg_replace('/[\r\n]+/', ' ', $subject) ?? '';
    $safeName = str_replace(["\r", "\n"], ' ', (string) ($config['from_name'] ?? 'Gadget 50'));
    $headers = 'From: ' . $safeName . ' <' . $from . ">\r\nTo: <" . $to . ">\r\nSubject: " . $safeSubject . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    fwrite($socket, $headers . chunk_split(base64_encode($body), 76, "\r\n") . "\r\n.\r\n");
    $sent = $is($read($socket), ['250']);
    fwrite($socket, "QUIT\r\n"); fclose($socket);
    return $sent;
}

function sendEmail(string $to, string $subject, string $body): bool
{
    return emailServiceAvailable() && sendSmtpMail($to, $subject, $body, getMailConfig());
}

function sendUserEmailVerification(array $user): bool
{
    $token = generateSecureToken();
    $pdo = Database::getInstance();
    $pdo->prepare('UPDATE users SET email_verification_token = :token, email_verification_expires_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR) WHERE id = :id')->execute([':token' => $token, ':id' => (int) $user['id']]);
    return sendEmail((string) $user['email'], 'Verify your email', '<p><a href="' . e(appUrl('verify-email.php?token=' . $token)) . '">Verify your email address</a></p>');
}

function sendPasswordResetEmail(array $user): bool
{
    $token = generateSecureToken();
    $pdo = Database::getInstance();
    $pdo->prepare('UPDATE users SET password_reset_token = :token, password_reset_expires_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR) WHERE id = :id')->execute([':token' => $token, ':id' => (int) $user['id']]);
    return sendEmail((string) $user['email'], 'Password reset', '<p><a href="' . e(appUrl('reset-password.php?token=' . $token)) . '">Reset your password</a></p>');
}

function sendAdminNewUserEmail(array $user): bool
{
    $to = getSetting('admin_alert_email', '');
    return $to === '' ? true : sendEmail($to, 'New user registration', '<p>Username: ' . e((string) $user['username']) . '</p>');
}

function sendTwoFactorCodeEmail(array $user, string $code): bool
{
    return sendEmail((string) $user['email'], 'Your two-factor login code', '<p>Your verification code is: <strong>' . e($code) . '</strong></p>');
}
