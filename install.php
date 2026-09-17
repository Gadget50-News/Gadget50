<?php

declare(strict_types=1);

session_start();

if (file_exists(__DIR__ . '/install.lock')) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/functions.php';

$status = '';
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $dbHost = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $dbName = trim((string) ($_POST['db_name'] ?? ''));
    $dbUser = trim((string) ($_POST['db_user'] ?? ''));
    $dbPass = (string) ($_POST['db_password'] ?? '');
    $adminUsername = trim((string) ($_POST['admin_username'] ?? ''));
    $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
    $adminPassword = (string) ($_POST['admin_password'] ?? '');

    if ($dbHost === '' || $dbName === '' || $dbUser === '' || $adminUsername === '' ||
        !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 8) {
        $status = 'Complete all fields, use a valid email, and choose a password of at least 8 characters.';
    } else {
        try {
            $pdo = new PDO('mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4', $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $schema = file_get_contents(__DIR__ . '/database/schema.sql');
            if ($schema === false) {
                throw new RuntimeException('Unable to load the schema file.');
            }
            $pdo->beginTransaction();
            $pdo->exec($schema);
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, status, created_at) VALUES (:username, :email, :password_hash, :role, :status, NOW())');
            $stmt->execute([':username' => $adminUsername, ':email' => $adminEmail, ':password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT), ':role' => 'super_admin', ':status' => 'active']);
            $pdo->commit();

            $config = "<?php\ndeclare(strict_types=1);\n\ndefine('DB_HOST', " . var_export($dbHost, true) . ");\ndefine('DB_NAME', " . var_export($dbName, true) . ");\ndefine('DB_USER', " . var_export($dbUser, true) . ");\ndefine('DB_PASS', " . var_export($dbPass, true) . ");\ndefine('APP_NAME', 'Gadget 50');\ndefine('APP_ROOT', __DIR__);\n";
            if (file_put_contents(__DIR__ . '/config.php', $config, LOCK_EX) === false || file_put_contents(__DIR__ . '/install.lock', date('c'), LOCK_EX) === false) {
                throw new RuntimeException('Could not write installer lock/configuration. Check permissions.');
            }
            header('Location: index.php?installed=1');
            exit;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $status = 'Installation failed. Check the database details and file permissions.';
        }
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Gadget 50 Installer</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-lg-9"><div class="card shadow border-0"><div class="card-header bg-dark text-white"><h2 class="mb-0">Gadget 50 Installation Wizard</h2></div><div class="card-body p-4">
<?php if ($status !== ''): ?><div class="alert alert-danger"><?= e($status) ?></div><?php endif; ?>
<form method="post" autocomplete="off"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="row g-4"><div class="col-md-6"><h4>Database Configuration</h4><label class="form-label">MySQL Host</label><input class="form-control mb-3" name="db_host" value="<?= e((string) ($old['db_host'] ?? 'localhost')) ?>" required><label class="form-label">Database Name</label><input class="form-control mb-3" name="db_name" required><label class="form-label">Database User</label><input class="form-control mb-3" name="db_user" required><label class="form-label">Database Password</label><input type="password" class="form-control" name="db_password"></div><div class="col-md-6"><h4>Super Admin Account</h4><label class="form-label">Username</label><input class="form-control mb-3" name="admin_username" required><label class="form-label">Email</label><input type="email" class="form-control mb-3" name="admin_email" required><label class="form-label">Password</label><input type="password" class="form-control" name="admin_password" minlength="8" required></div></div><button class="btn btn-primary btn-lg mt-4">Install Gadget 50</button></form>
</div></div></div></div></div></body></html>
