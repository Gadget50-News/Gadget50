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

    if ($id > 0 && $action === 'delete') {
        $pdo->prepare('DELETE FROM news WHERE id = :id')->execute([':id' => $id]);
        setFlash('success', 'News deleted.');
    } elseif ($id > 0 && $action === 'status') {
        $allowed = ['pending', 'published', 'archived', 'draft'];
        $status = (string) ($_POST['status'] ?? 'pending');
        if (in_array($status, $allowed, true)) {
            $pdo->prepare('UPDATE news SET status = :status, updated_at = NOW() WHERE id = :id')->execute([':status' => $status, ':id' => $id]);
            setFlash('success', 'News status updated.');
        }
    }
    redirect('news.php');
}

$news = $pdo->query('SELECT n.*, c.name AS category_name, u.username FROM news n LEFT JOIN categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id ORDER BY n.created_at DESC')->fetchAll();
$flash = getFlash();
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manage News | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-5"><div class="d-flex justify-content-between align-items-center mb-4"><h2>Manage News</h2><a href="index.php" class="btn btn-outline-secondary">Dashboard</a></div>
<?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
<div class="card shadow-sm border-0"><div class="card-body"><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($news as $item): ?><tr><td><a href="../news.php?id=<?= (int) $item['id'] ?>"><?= e((string) $item['title']) ?></a></td><td><?= e((string) ($item['username'] ?? 'Unknown')) ?></td><td><?= e((string) ($item['category_name'] ?? 'General')) ?></td><td><form method="post" class="d-flex gap-2"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><select name="status" class="form-select form-select-sm"><option value="pending" <?= $item['status'] === 'pending' ? 'selected' : '' ?>>Pending</option><option value="published" <?= $item['status'] === 'published' ? 'selected' : '' ?>>Published</option><option value="archived" <?= $item['status'] === 'archived' ? 'selected' : '' ?>>Archived</option><option value="draft" <?= $item['status'] === 'draft' ? 'selected' : '' ?>>Draft</option></select><button class="btn btn-sm btn-primary">Update</button></form></td><td><a class="btn btn-sm btn-outline-primary" href="news-edit.php?id=<?= (int) $item['id'] ?>">Edit</a><form method="post" class="d-inline" onsubmit="return confirm('Delete this news item?');"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div></div></div></div></body></html>
