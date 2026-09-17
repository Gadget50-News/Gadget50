<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$pdo = Database::getInstance();
$allowedStatuses = ['pending', 'published', 'archived', 'draft'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0 && $action === 'delete') {
        $pdo->prepare('DELETE FROM news WHERE id = :id')->execute([':id' => $id]);
        setFlash('success', 'News deleted.');
    } elseif ($id > 0 && $action === 'status') {
        $status = (string) ($_POST['status'] ?? 'pending');
        if (in_array($status, $allowedStatuses, true)) {
            $pdo->prepare('UPDATE news SET status = :status, updated_at = NOW() WHERE id = :id')->execute([':status' => $status, ':id' => $id]);
            setFlash('success', 'News status updated.');
        }
    }
    redirect('news.php');
}

$news = $pdo->query(
    'SELECT n.id, n.title, n.status, n.is_anonymous, n.created_at,
            c.name AS category_name, u.username
     FROM news n
     LEFT JOIN categories c ON c.id = n.category_id
     LEFT JOIN users u ON u.id = n.author_id
     ORDER BY n.created_at DESC'
)->fetchAll();
$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage News | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div><h2 class="mb-1">Manage News</h2><p class="text-muted mb-0">Approve, publish, edit, archive, or delete submissions.</p></div>
        <div class="d-flex gap-2"><a href="../submit.php" class="btn btn-success">➕ Post New News</a><a href="index.php" class="btn btn-outline-secondary">Dashboard</a></div>
    </div>
    <?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
    <div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (!$news): ?><tr><td colspan="5" class="text-center text-muted py-4">No news submissions yet.</td></tr><?php endif; ?>
        <?php foreach ($news as $item): ?><tr>
            <td><a href="../news.php?id=<?= (int) $item['id'] ?>"><?= e((string) $item['title']) ?></a></td>
            <td><?= (int) $item['is_anonymous'] === 1 ? 'Anonymous' : e((string) ($item['username'] ?? 'Unknown')) ?></td>
            <td><?= e((string) ($item['category_name'] ?? 'General')) ?></td>
            <td><form method="post" class="d-flex gap-2"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><select name="status" class="form-select form-select-sm"><?php foreach ($allowedStatuses as $status): ?><option value="<?= e($status) ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-primary">Save</button></form></td>
            <td><a class="btn btn-sm btn-outline-primary" href="news-edit.php?id=<?= (int) $item['id'] ?>">Edit</a><form method="post" class="d-inline" onsubmit="return confirm('Delete this news item?');"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></td>
        </tr><?php endforeach; ?>
        </tbody>
    </table></div></div></div>
</main>
</body>
</html>
