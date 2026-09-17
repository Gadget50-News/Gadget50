<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$pdo = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM news WHERE id = :id')->execute([':id' => $id]);
            setFlash('success', 'News deleted.');
        }
        redirect('news.php');
    }

    if ($action === 'status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'pending');
        if ($id > 0) {
            $pdo->prepare('UPDATE news SET status = :status WHERE id = :id')->execute([':status' => $status, ':id' => $id]);
            setFlash('success', 'News status updated.');
        }
        redirect('news.php');
    }
}

$news = $pdo->query(
    'SELECT n.*, c.name AS category_name, u.username FROM news n LEFT JOIN categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id ORDER BY n.created_at DESC'
)->fetchAll();
$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage News | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage News</h2>
            <a href="index.php" class="btn btn-outline-secondary">Dashboard</a>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($news as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($item['username'] ?? 'Anonymous'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($item['category_name'] ?? 'General'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <form method="post" class="d-flex gap-2 align-items-center">
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="pending" <?= $item['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="published" <?= $item['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                                            <option value="archived" <?= $item['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                                        </select>
                                        <button class="btn btn-sm btn-primary" type="submit">Update</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Delete this news item?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
