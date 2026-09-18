<?php
declare(strict_types=1);

function appBasePath(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $directory = dirname($script);
    if ($directory === '/' || $directory === '.' || $directory === '\\') {
        return '';
    }
    if (str_ends_with($directory, '/admin')) {
        $directory = dirname($directory);
    }
    return '/' . trim($directory, '/');
}

function appUrl(string $path = ''): string
{
    $path = trim($path);
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    $configured = rtrim(getSetting('site_url', ''), '/');
    if ($configured !== '' && preg_match('#^https?://#i', $configured) === 1) {
        return $configured . ($path === '' ? '' : '/' . ltrim($path, '/'));
    }

    $base = rtrim(appBasePath(), '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . appUrl($path), true, 302);
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }

    $token = (string) ($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(csrfToken(), $token)) {
        http_response_code(419);
        exit('Invalid or expired form token.');
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
        error_log('Current user lookup failed: ' . $e->getMessage());
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

function setSetting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :updated_value, updated_at = CURRENT_TIMESTAMP');
    $stmt->execute([':key' => $key, ':value' => $value, ':updated_value' => $value]);
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
    $value = trim((string) preg_replace('/-+/', '-', (string) $value), '-');
    return $value !== '' ? $value : 'item-' . bin2hex(random_bytes(4));
}

function sanitizeSlug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9-]+/', '-', $value);
    return trim((string) preg_replace('/-+/', '-', (string) $value), '-');
}

function isSafeMenuUrl(string $url): bool
{
    $url = trim($url);
    if ($url === '' || preg_match('/^(javascript|data|vbscript):/i', $url)) return false;
    foreach (['http://', 'https://', '/', '#', 'mailto:', 'tel:'] as $prefix) {
        if (str_starts_with(strtolower($url), $prefix)) return true;
    }
    return preg_match('/^[a-zA-Z0-9_\/\-.?#=&%]+$/', $url) === 1;
}

function validMenuPosition(string $position): bool
{
    return in_array($position, ['header', 'footer', 'sidebar'], true);
}

function isReservedRoute(string $value): bool
{
    return in_array(strtolower(trim($value)), ['admin', 'login', 'register', 'dashboard', 'logout', 'news', 'category', 'tag', 'author', 'user', 'search', 'install', '404', 'submit', 'index'], true);
}

function generateSecureToken(int $length = 32): string
{
    return bin2hex(random_bytes(max(16, (int) ceil($length / 2))));
}

function generateUniqueSlug(PDO $pdo, string $table, string $column, string $value, ?int $id = null): string
{
    $base = sanitizeSlug($value) ?: 'untitled';
    for ($counter = 0; ; $counter++) {
        $candidate = $counter === 0 ? $base : $base . '-' . ($counter + 1);
        $sql = 'SELECT id FROM ' . $table . ' WHERE ' . $column . ' = :slug' . ($id !== null ? ' AND id != :id' : '');
        $stmt = $pdo->prepare($sql);
        $params = [':slug' => $candidate];
        if ($id !== null) $params[':id'] = $id;
        $stmt->execute($params);
        if (!$stmt->fetch()) return $candidate;
    }
}

function uploadNewsImage(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed.');
    $tmpName = (string) ($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmpName)) throw new RuntimeException('Invalid upload source.');
    if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > 5 * 1024 * 1024) throw new RuntimeException('Images must be between 1 byte and 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG, WEBP, and GIF images are allowed.');
    $directory = __DIR__ . '/../uploads/news';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Upload directory is unavailable.');
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($tmpName, $directory . '/' . $name)) throw new RuntimeException('Could not save the image.');
    return 'uploads/news/' . $name;
}

function rateLimitCheck(string $key): bool
{
    $now = time();
    $bucket = $_SESSION['rate_limit'][$key] ?? ['count' => 0, 'timestamp' => $now];
    if ($now - (int) $bucket['timestamp'] > 300) $bucket = ['count' => 0, 'timestamp' => $now];
    if ((int) $bucket['count'] >= 5) return false;
    $_SESSION['rate_limit'][$key] = ['count' => (int) $bucket['count'] + 1, 'timestamp' => $now];
    return true;
}
