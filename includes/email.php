<?php

declare(strict_types=1);

function getMailConfig(): array
{
    return [
        'provider' => getSetting('email_provider', 'zoho'),
        'host' => getSetting('smtp_host', 'smtp.zoho.com'),
        'port' => (int) getSetting('smtp_port', '587'),
        'encryption' => getSetting('smtp_encryption', 'tls'),
        'username' => getSetting('smtp_username', ''),
        'password' => getSetting('smtp_password', ''),
        'from_name' => getSetting('smtp_from_name', APP_NAME),
        'from_email' => getSetting('smtp_from_email', 'noreply@localhost'),
        'site_url' => getSetting('site_url', 'http://localhost'),
    ];
}

function generateSecureToken(int $length = 32): string
{
    return bin2hex(random_bytes(max(16, (int) ceil($length / 2))));
}

function sendSmtpMail(string $to, string $subject, string $body, array $config): bool
{
    $host = trim((string) ($config['host'] ?? ''));
    $username = trim((string) ($config['username'] ?? ''));
    $password = trim((string) ($config['password'] ?? ''));
    $fromEmail = trim((string) ($config['from_email'] ?? ''));
    $fromName = trim((string) ($config['from_name'] ?? APP_NAME));

    if ($host === '' || $username === '' || $password === '' || $fromEmail === '') {
        return false;
    }

    $port = (int) ($config['port'] ?? 587);
    $encryption = strtolower(trim((string) ($config['encryption'] ?? 'tls')));
    $socket = @fsockopen($host, $port, $errno, $errstr, 15);
    if ($socket === false) {
        error_log('SMTP connection failed: ' . $errstr . ' (' . $errno . ')');
        return false;
    }

    $read = static function ($socket): string {
        $response = '';
        while (!feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) {
                break;
            }
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return trim($response);
    };

    $response = $read($socket);
    if (strpos($response, '220') !== 0) {
        fclose($socket);
        return false;
    }

    $commands = [
        'EHLO ' . parse_url(($config['site_url'] ?? 'http://localhost'), PHP_URL_HOST) ?: 'localhost',
        'AUTH LOGIN',
        base64_encode($username),
        base64_encode($password),
        'MAIL FROM:<' . $fromEmail . '>',
        'RCPT TO:<' . $to . '>',
        'DATA',
        "From: \"" . str_replace('"', '', $fromName) . "\" <" . $fromEmail . ">\r\n"
            . "To: <" . $to . ">\r\n"
            . "Subject: " . $subject . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($body), 76, "\r\n")
            . "\r\n.\r\n",
        'QUIT',
    ];

    foreach ($commands as $command) {
        if ($command === 'QUIT') {
            fwrite($socket, $command . "\r\n");
            $read($socket);
            break;
        }

        if ($command === 'AUTH LOGIN' || $command === 'DATA') {
            fwrite($socket, $command . "\r\n");
            $result = $read($socket);
            if ($command === 'DATA' && strpos($result, '354') !== 0) {
                fclose($socket);
                return false;
            }
            continue;
        }

        fwrite($socket, $command . "\r\n");
        $result = $read($socket);
        if (
            in_array($command, ['EHLO ' . parse_url(($config['site_url'] ?? 'http://localhost'), PHP_URL_HOST) ?: 'localhost', 'MAIL FROM:<' . $fromEmail . '>', 'RCPT TO:<' . $to . '>'], true)
            && strpos($result, '250') !== 0 && strpos($result, '220') !== 0 && strpos($result, '235') !== 0 && strpos($result, '251') !== 0
        ) {
            fclose($socket);
            return false;
        }
    }

    fclose($socket);
    return true;
}

function sendEmail(string $to, string $subject, string $body): bool
{
    $config = getMailConfig();
    $host = trim((string) ($config['host'] ?? ''));
    $username = trim((string) ($config['username'] ?? ''));
    $password = trim((string) ($config['password'] ?? ''));

    if ($host !== '' && $username !== '' && $password !== '') {
        return sendSmtpMail($to, $subject, $body, $config);
    }

    return mail($to, $subject, $body, "From: " . $config['from_name'] . " <" . $config['from_email'] . ">\r\nContent-Type: text/html; charset=UTF-8\r\n");
}

function sendUserEmailVerification(array $user): bool
{
    $config = getMailConfig();
    $token = generateSecureToken();
    $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60 * 24));

    $pdo = Database::getInstance();
    $stmt = $pdo->prepare('UPDATE users SET email_verification_token = :token, email_verification_expires_at = :expires WHERE id = :id');
    $stmt->execute([
        ':token' => $token,
        ':expires' => $expiresAt,
        ':id' => (int) $user['id'],
    ]);

    $verifyUrl = rtrim((string) $config['site_url'], '/') . '/verify-email.php?token=' . urlencode($token);
    $subject = 'Verify your email address';
    $body = '<p>Hello ' . e((string) ($user['username'] ?? 'member')) . ',</p>'
        . '<p>Thanks for creating your account. Please verify your email address by clicking the link below:</p>'
        . '<p><a href="' . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . '">Verify Email</a></p>'
        . '<p>If the button does not work, copy this link into your browser:</p>'
        . '<p>' . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . '</p>';

    return sendEmail((string) $user['email'], $subject, $body);
}

function sendPasswordResetEmail(array $user): bool
{
    $config = getMailConfig();
    $token = generateSecureToken();
    $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60));

    $pdo = Database::getInstance();
    $stmt = $pdo->prepare('UPDATE users SET password_reset_token = :token, password_reset_expires_at = :expires WHERE id = :id');
    $stmt->execute([
        ':token' => $token,
        ':expires' => $expiresAt,
        ':id' => (int) $user['id'],
    ]);

    $resetUrl = rtrim((string) $config['site_url'], '/') . '/reset-password.php?token=' . urlencode($token);
    $subject = 'Reset your password';
    $body = '<p>Hello ' . e((string) ($user['username'] ?? 'member')) . ',</p>'
        . '<p>We received a request to reset your password. Use the link below to continue:</p>'
        . '<p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Reset Password</a></p>'
        . '<p>If you did not request this, you can ignore this email.</p>';

    return sendEmail((string) $user['email'], $subject, $body);
}

function sendAdminNewUserEmail(array $user): bool
{
    $adminEmail = getSetting('admin_alert_email', '');
    if ($adminEmail === '') {
        return true;
    }

    $subject = 'New user registered on ' . APP_NAME;
    $body = '<p>A new user has just registered.</p>'
        . '<p><strong>Username:</strong> ' . e((string) ($user['username'] ?? '')) . '</p>'
        . '<p><strong>Email:</strong> ' . e((string) ($user['email'] ?? '')) . '</p>'
        . '<p><strong>Registered At:</strong> ' . date('Y-m-d H:i:s') . '</p>';

    return sendEmail($adminEmail, $subject, $body);
}

function sendTwoFactorCodeEmail(array $user, string $code): bool
{
    $subject = 'Your two-factor login code';
    $body = '<p>Hello ' . e((string) ($user['username'] ?? 'member')) . ',</p>'
        . '<p>Your verification code is: <strong>' . e($code) . '</strong></p>'
        . '<p>This code is valid for a short time only.</p>';

    return sendEmail((string) $user['email'], $subject, $body);
}
