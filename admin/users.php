<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$pdo = Database::getInstance();
$me = (int) ($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int) ($_POST['user_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($id === $me && $action === 'status' && ($_POST['status'] ?? '') !== 'active') {
        setFlash('danger', 'You cannot deactivate your own account.');
    } elseif ($id === $me && $action === 'role' && ($_POST['role'] ?? '') !== 'super_admin') {
        setFlash('danger', 'You cannot remove your own Super Admin role.');
    } elseif ($id > 0 && $action === 'role' && in_array((string) ($_POST['role'] ?? ''), ['member', 'editor', 'super_admin'], true)) {
        $pdo->prepare('UPDATE users SET role = :role WHERE id = :id')->execute([':role' => $_POST['role'], ':id' => $id]);
        setFlash('success', 'User role updated.');
    } elseif ($id > 0 && $action === 'status' && in_array((string) ($_POST['status'] ?? ''), ['active', 'inactive', 'banned'], true)) {
        $pdo->prepare('UPDATE users SET status = :status WHERE id = :id')->execute([':status' => $_POST['status'], ':id' => $id]);
        setFlash('success', 'User status updated.');
    } elseif ($id > 0 && $action === 'twofa') {
        $twoFa = (isset($_POST['two_factor_enabled']) && (string) $_POST['two_factor_enabled'] === '1') ? 1 : 0;
        if ($id === $me && $twoFa === 0) {
            setFlash('danger', 'You cannot disable your own 2FA requirement while you are signed in as the current admin.');
        } else {
            $pdo->prepare('UPDATE users SET two_factor_enabled = :two_factor_enabled WHERE id = :id')->execute([
                ':two_factor_enabled' => $twoFa,
                ':id' => $id,
            ]);
            setFlash('success', '2FA setting updated.');
        }
    }

    redirect('users.php');
}

$users = $pdo->query('SELECT id, username, email, role, status, two_factor_enabled, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Users | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <div class="d-flex justify-content-between mb-4">
        <h2>Manage Users</h2>
        <a href="index.php" class="btn btn-outline-secondary">Dashboard</a>
    </div>
    <?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>2FA</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= e((string) ($user['username'] ?? '')) ?></td>
                            <td><?= e((string) ($user['email'] ?? '')) ?></td>
                            <td>
                                <form method="post" class="d-flex gap-2 align-items-center">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="role">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                    <select name="role" class="form-select form-select-sm" aria-label="Role">
                                        <option value="member" <?= ((string) ($user['role'] ?? '') === 'member') ? 'selected' : '' ?>>Member</option>
                                        <option value="editor" <?= ((string) ($user['role'] ?? '') === 'editor') ? 'selected' : '' ?>>Editor</option>
                                        <option value="super_admin" <?= ((string) ($user['role'] ?? '') === 'super_admin') ? 'selected' : '' ?>>Super Admin</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                </form>
                            </td>
                            <td>
                                <form method="post" class="d-flex gap-2 align-items-center">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                    <select name="status" class="form-select form-select-sm" aria-label="Status">
                                        <option value="active" <?= ((string) ($user['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ((string) ($user['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                        <option value="banned" <?= ((string) ($user['status'] ?? '') === 'banned') ? 'selected' : '' ?>>Banned</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
                                </form>
                            </td>
                            <td>
                                <form method="post" class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="twofa">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="two_factor_enabled" value="1" <?= ((int) ($user['two_factor_enabled'] ?? 0) === 1) ? 'checked' : '' ?>>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-warning">Apply</button>
                                </form>
                            </td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>
