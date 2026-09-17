<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$pdo = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $pdo->prepare('DELETE FROM menus WHERE id = :id')->execute([':id' => $id]);
        setFlash('success', 'Menu deleted.');
        redirect('menus.php');
    }

    if ($action === 'create') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        $position = (string) ($_POST['position'] ?? 'header');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);

        if ($title === '' || strlen($title) > 120 || $url === '' || strlen($url) > 255 || !validMenuPosition($position) || !isSafeMenuUrl($url)) {
            setFlash('danger', 'Enter a valid title, safe URL, position, and order.');
            redirect('menus.php');
        }

        $pdo->prepare('INSERT INTO menus (title, url, position, sort_order, status) VALUES (:title, :url, :position, :sort_order, :status)')->execute([
            ':title' => $title,
            ':url' => $url,
            ':position' => $position,
            ':sort_order' => $sortOrder,
            ':status' => 'active',
        ]);
        setFlash('success', 'Menu item created.');
    }

    redirect('menus.php');
}

$menus = $pdo->query('SELECT id, title, url, position, sort_order FROM menus ORDER BY position, sort_order ASC')->fetchAll();
$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menus | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="d-flex justify-content-between mb-4"><h2>Menu Manager</h2><a href="index.php" class="btn btn-outline-secondary">Dashboard</a></div>
        <?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
        <div class="card border-0 shadow-sm mb-4"><div class="card-body"><form method="post" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="create">
            <div class="col-md-4"><label class="form-label">Title</label><input class="form-control" name="title" maxlength="120" required></div>
            <div class="col-md-4"><label class="form-label">URL</label><input class="form-control" name="url" maxlength="255" required></div>
            <div class="col-md-2"><label class="form-label">Position</label><select class="form-select" name="position"><option value="header">Header</option><option value="footer">Footer</option><option value="sidebar">Sidebar</option></select></div>
            <div class="col-md-2"><label class="form-label">Order</label><input type="number" class="form-control" name="sort_order" value="0"></div>
            <div class="col-12"><button class="btn btn-primary">Add Menu</button></div>
        </form></div></div>
        <div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Title</th><th>URL</th><th>Position</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($menus as $menu): ?>
            <tr>
                <td><?= e((string) $menu['title']) ?></td>
                <td><?= e((string) $menu['url']) ?></td>
                <td><?= e((string) $menu['position']) ?></td>
                <td>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $menu['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this menu item?')">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        </table></div></div></div>
    </main>
</body>
</html>
