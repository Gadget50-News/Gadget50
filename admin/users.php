<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('../login.php');
requireRole('super_admin', '../login.php');
$pdo = Database::getInstance();
$me = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int) ($_POST['user_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($id === $me && $action === 'status' && ($_POST['status'] ?? '') !== 'active') {
        setFlash('danger', 'You cannot deactivate your own account.');
    } elseif ($id === $me && $action === 'role' && ($_POST['role'] ?? '') !== 'super_admin') {
        setFlash('danger', 'You cannot remove your own Super Admin role.');
    } elseif ($id > 0 && $action === 'role' && in_array($_POST['role'] ?? '', ['member', 'editor', 'super_admin'], true)) {
        $pdo->prepare('UPDATE users SET role = :role WHERE id = :id')->execute([':role' => $_POST['role'], ':id' => $id]);
        setFlash('success', 'User role updated.');
    } elseif ($id > 0 && $action === 'status' && in_array($_POST['status'] ?? '', ['active', 'inactive', 'banned'], true)) {
        $pdo->prepare('UPDATE users SET status = :status WHERE id = :id')->execute([':status' => $_POST['status'], ':id' => $id]);
        setFlash('success', 'User status updated.');
    }
    redirect('users.php');
}

$users = $pdo->query('SELECT id, username, email, role, status, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$flash = getFlash();
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Users | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5"><div class="d-flex justify-content-between mb-4"><h2>Manage Users</h2><a href="index.php" class="btn btn-outline-secondary">Dashboard</a></div><?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?><div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th></tr></thead><tbody><?php foreach ($users as $user): ?><tr><td><?= e((string) $user['username']) ?></td><td><?= e((string) $user['email']) ?></td><td><form method="post" class="d-flex gap-2"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="role"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>"><select name="role" class="form-select form-select-sm"><option value="member" <?= $user['role'] === 'member' ? 'selected' : '' ?>>Member</option><option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>Editor</option><option value="super_admin" <?= $user['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option></select><button class="btn btn-sm btn-primary">Save</button></form></td><td><form method="post" class="d-flex gap-2"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>"><select name="status" class="form-select form-select-sm"><option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option><option value="banned" <?= $user['status'] === 'banned' ? 'selected' : '' ?>>Banned</option></select><button class="btn btn-sm btn-secondary">Save</button></form></td></tr><?php endforeach; ?></tbody></table></div></div></div></main></body></html>
