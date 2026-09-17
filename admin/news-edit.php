<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$pdo = Database::getInstance();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('news.php');
}

$stmt = $pdo->prepare('SELECT * FROM news WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$item = $stmt->fetch();
if (!$item) {
    http_response_code(404);
    exit('News item not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'pending');
    $anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    if ($title === '' || strlen($title) > 255 || $content === '' || $categoryId <= 0 || !in_array($status, ['draft', 'pending', 'published', 'archived'], true)) {
        setFlash('danger', 'Please provide valid news details.');
        redirect('news-edit.php?id=' . $id);
    }

    $update = $pdo->prepare('UPDATE news SET title = :title, slug = :slug, content = :content, category_id = :category_id, is_anonymous = :is_anonymous, status = :status, updated_at = NOW() WHERE id = :id');
    $update->execute([':title' => $title, ':slug' => slugify($title), ':content' => $content, ':category_id' => $categoryId, ':is_anonymous' => $anonymous, ':status' => $status, ':id' => $id]);
    setFlash('success', 'News updated successfully.');
    redirect('news.php');
}

$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$flash = getFlash();
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Edit News | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-5"><div class="d-flex justify-content-between align-items-center mb-4"><h2>Edit News</h2><a href="news.php" class="btn btn-outline-secondary">Back</a></div><?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?><div class="card border-0 shadow-sm"><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= $id ?>"><div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" maxlength="255" value="<?= e((string) $item['title']) ?>" required></div><div class="mb-3"><label class="form-label">Category</label><select class="form-select" name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $item['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e((string) $category['name']) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label">Content</label><textarea class="form-control" name="content" rows="10" required><?= e((string) $item['content']) ?></textarea></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach (['draft', 'pending', 'published', 'archived'] as $status): ?><option value="<?= $status ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></div><div class="col-md-6 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_anonymous" id="is_anonymous" value="1" <?= (int) $item['is_anonymous'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="is_anonymous">Display author as Anonymous</label></div></div></div><button class="btn btn-primary mt-4">Save Changes</button></form></div></div></div></body></html>
