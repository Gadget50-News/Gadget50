<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/functions.php';

if (defined('DB_NAME') && file_exists(__DIR__ . '/install.lock')) {
    header('Location: index.php');
    exit;
}

$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $dbName = trim((string) ($_POST['db_name'] ?? ''));
    $dbUser = trim((string) ($_POST['db_user'] ?? ''));
    $dbPass = (string) ($_POST['db_password'] ?? '');
    $adminUsername = trim((string) ($_POST['admin_username'] ?? ''));
    $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
    $adminPassword = (string) ($_POST['admin_password'] ?? '');

    $required = [
        'MySQL Host' => $dbHost,
        'Database Name' => $dbName,
        'Database User' => $dbUser,
        'Super Admin Username' => $adminUsername,
        'Super Admin Email' => $adminEmail,
        'Super Admin Password' => $adminPassword,
    ];

    foreach ($required as $label => $value) {
        if ($value === '') {
            $status = $label . ' is required.';
            break;
        }
    }

    if ($status === '') {
        $dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $schema = file_get_contents(__DIR__ . '/database/schema.sql');
            if ($schema === false) {
                throw new RuntimeException('The schema file could not be read.');
            }

            $pdo->exec($schema);

            $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
            $userStmt = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash, role, status, created_at) VALUES (:username, :email, :password_hash, :role, :status, NOW())'
            );
            $userStmt->execute([
                ':username' => $adminUsername,
                ':email' => $adminEmail,
                ':password_hash' => $passwordHash,
                ':role' => 'super_admin',
                ':status' => 'active',
            ]);

            $configContent = <<<'PHP'
<?php

declare(strict_types=1);

define('DB_HOST', '%s');
define('DB_NAME', '%s');
define('DB_USER', '%s');
define('DB_PASS', '%s');
define('APP_NAME', 'Gadget 50');
define('APP_ROOT', __DIR__);
PHP;

            $configContent = sprintf(
                $configContent,
                addslashes($dbHost),
                addslashes($dbName),
                addslashes($dbUser),
                addslashes($dbPass)
            );

            if (file_put_contents(__DIR__ . '/config.php', $configContent) === false) {
                throw new RuntimeException('config.php could not be written. Please confirm write permissions.');
            }

            file_put_contents(__DIR__ . '/install.lock', date('c'));

            header('Location: index.php?installed=1');
            exit;
        } catch (Throwable $e) {
            $status = 'Installation failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gadget 50 Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #eef2ff, #f8fafc); }
        .card { border: 0; border-radius: 18px; }
        .card-header { border-radius: 18px 18px 0 0 !important; }
        .form-label { font-weight: 600; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card shadow-lg">
                    <div class="card-header bg-dark text-white py-3">
                        <h2 class="mb-0">Gadget 50 Installation Wizard</h2>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($status !== ''): ?>
                            <div class="alert alert-danger" role="alert"><?= $status ?></div>
                        <?php endif; ?>

                        <form method="post" autocomplete="off">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h4 class="mb-3">Database Configuration</h4>
                                    <div class="mb-3">
                                        <label for="db_host" class="form-label">MySQL Host</label>
                                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="db_name" class="form-label">Database Name</label>
                                        <input type="text" class="form-control" id="db_name" name="db_name" placeholder="gadget50_db" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="db_user" class="form-label">Database User</label>
                                        <input type="text" class="form-control" id="db_user" name="db_user" placeholder="db_user" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="db_password" class="form-label">Database Password</label>
                                        <input type="password" class="form-control" id="db_password" name="db_password" placeholder="••••••••" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h4 class="mb-3">Super Admin Account</h4>
                                    <div class="mb-3">
                                        <label for="admin_username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="admin_username" name="admin_username" placeholder="admin" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="admin_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="admin_email" name="admin_email" placeholder="admin@example.com" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="admin_password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="admin_password" name="admin_password" placeholder="Strong password" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <small class="text-muted">This wizard creates the database tables, inserts default settings, and locks the installer.</small>
                                <button type="submit" class="btn btn-primary btn-lg">Install Gadget 50</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
