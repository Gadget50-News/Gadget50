<?php
declare(strict_types=1);

function appBasePath(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = dirname($script);
    if ($base === '/' || $base === '.' || $base === '\\') return '';
    if (str_ends_with($base, '/admin')) $base = dirname($base);
    return '/' . trim($base, '/');
}

function appUrl(string $path = ''): string
{
    $path = trim($path);
    if (preg_match('#^https?://#i', $path) === 1) return $path;
    $base = rtrim((string) getSetting('site_url', ''), '/');
    if ($base !== '' && preg_match('#^https?://#i', $base) === 1) {
        return $base . '/' . ltrim($path, '/');
    }
    return rtrim(appBasePath(), '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . appUrl($path), true, 302);
    exit;
}

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function setFlash(string $type, string $message): void { $_SESSION['flash'] = ['type' => $type, 'message' => $message]; }
function getFlash(): ?array { $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return is_array($flash) ? $flash : null; }
function csrfToken(): string { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); return (string) $_SESSION['csrf_token']; }
function verifyCsrf(): void { if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); } $token = (string) ($_POST['csrf_token'] ?? ''); if ($token === '' || !hash_equals(csrfToken(), $token)) { http_response_code(419); exit('Invalid or expired form token.'); } }
function isLoggedIn(): bool { return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0; }
function currentUser(): ?array { if (!isLoggedIn()) return null; try { $s=Database::getInstance()->prepare('SELECT * FROM users WHERE id=:id LIMIT 1'); $s->execute([':id'=>(int)$_SESSION['user_id']]); return $s->fetch() ?: null; } catch (Throwable $e) { error_log('Current user lookup failed: '.$e->getMessage()); return null; } }
function requireLogin(string $redirectTo='login.php'): void { if (!isLoggedIn()) redirect($redirectTo); }
function requireRole(string $role, string $redirectTo='login.php'): void { $u=currentUser(); if (!$u || $u['role'] !== $role || $u['status'] !== 'active') redirect($redirectTo); }
function getSetting(string $key, string $default=''): string { try { $s=Database::getInstance()->prepare('SELECT setting_value FROM settings WHERE setting_key=:key LIMIT 1'); $s->execute([':key'=>$key]); $r=$s->fetch(); return ($r && $r['setting_value'] !== null) ? (string)$r['setting_value'] : $default; } catch (Throwable $e) { return $default; } }
function setSetting(PDO $pdo,string $key,string $value): void { $s=$pdo->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(:k,:v) ON DUPLICATE KEY UPDATE setting_value=:v2,updated_at=CURRENT_TIMESTAMP'); $s->execute([':k'=>$key,':v'=>$value,':v2'=>$value]); }
function slugify(string $value): string { $value=strtolower(trim($value)); $value=preg_replace('/[^a-z0-9]+/i','-',$value); $value=trim((string)preg_replace('/-+/','-',(string)$value),'-'); return $value !== '' ? $value : 'item-'.bin2hex(random_bytes(4)); }
function sanitizeSlug(string $value): string { return trim((string)preg_replace('/-+/','-',(string)preg_replace('/[^a-z0-9-]+/','-',strtolower(trim($value)))),'-'); }
function validMenuPosition(string $p): bool { return in_array($p,['header','footer','sidebar'],true); }
function isSafeMenuUrl(string $url): bool { $url=trim($url); if ($url==='' || preg_match('/^(javascript|data|vbscript):/i',$url)) return false; return preg_match('#^(https?://|/|#|mailto:|tel:|[a-zA-Z0-9_./?&=%-]+$)#i',$url)===1; }
function isReservedRoute(string $v): bool { return in_array(strtolower(trim($v)),['admin','login','register','dashboard','logout','news','category','tag','author','user','search','install','404','submit','index'],true); }
function generateSecureToken(int $length=32): string { return bin2hex(random_bytes(max(16,(int)ceil($length/2)))); }
function generateUniqueSlug(PDO $pdo,string $table,string $column,string $value,?int $id=null): string { $base=sanitizeSlug($value) ?: 'untitled'; $candidate=$base; for($i=2;;$i++){ $sql='SELECT id FROM '.$table.' WHERE '.$column.'=:slug'.($id!==null?' AND id!=:id':''); $s=$pdo->prepare($sql); $p=[':slug'=>$candidate]; if($id!==null)$p[':id']=$id; $s->execute($p); if(!$s->fetch())return $candidate; $candidate=$base.'-'.$i; } }
function uploadNewsImage(array $file): ?string { if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null; if(($file['error']??1)!==UPLOAD_ERR_OK)throw new RuntimeException('Image upload failed.'); $tmp=(string)($file['tmp_name']??''); if(!is_uploaded_file($tmp))throw new RuntimeException('Invalid upload source.'); if((int)($file['size']??0)<=0||(int)$file['size']>5*1024*1024)throw new RuntimeException('Images must be 1 byte to 5 MB.'); $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp); $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif']; if(!isset($allowed[$mime]))throw new RuntimeException('Only JPG, PNG, WEBP, and GIF images are allowed.'); $dir=__DIR__.'/../uploads/news'; if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))throw new RuntimeException('Upload directory unavailable.'); $name=bin2hex(random_bytes(16)).'.'.$allowed[$mime]; if(!move_uploaded_file($tmp,$dir.'/'.$name))throw new RuntimeException('Could not save image.'); return 'uploads/news/'.$name; }
