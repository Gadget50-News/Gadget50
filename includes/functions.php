<?php

declare(strict_types=1);

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(csrfToken(), $token)) {
        http_response_code(419);
        exit('Invalid or expired form token. Please go back and try again.');
    }
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
}

function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    try {
        $stmt = Database::getInstance()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int) $_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function requireLogin(string $redirectTo = 'login.php'): void
{
    if (!isLoggedIn()) redirect($redirectTo);
}

function requireRole(string $role, string $redirectTo = 'login.php'): void
{
    $user = currentUser();
    if (!$user || $user['role'] !== $role || $user['status'] !== 'active') redirect($redirectTo);
}

function getSetting(string $key, string $default = ''): string
{
    try {
        $stmt = Database::getInstance()->prepare('SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1');
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();
        return ($row && $row['setting_value'] !== null) ? (string) $row['setting_value'] : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function slugify(string $value): string
{
    $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
    return $slug !== '' ? $slug : 'item-' . bin2hex(random_bytes(4));
}

function validMenuPosition(string $position): bool
{
    return in_array($position, ['header', 'footer', 'sidebar'], true);
}

function uploadNewsImage(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('Image upload failed.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('Images must be 5 MB or smaller.');

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG, WEBP, and GIF images are allowed.');

    $directory = __DIR__ . '/../uploads/news';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Upload directory is unavailable.');
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) throw new RuntimeException('Could not save the image.');
    return 'uploads/news/' . $name;
}
