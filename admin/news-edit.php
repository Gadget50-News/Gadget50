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
    $slugInput = trim((string) ($_POST['slug'] ?? ''));
    $anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    if ($title === '' || strlen($title) > 255 || $content === '' || $categoryId <= 0 || !in_array($status, ['draft', 'pending', 'published', 'archived'], true)) {
        setFlash('danger', 'Please provide valid news details.');
        redirect('news-edit.php?id=' . $id);
    }

    $slug = $slugInput !== '' ? sanitizeSlug($slugInput) : sanitizeSlug((string) $title);
    if ($slug === '' || isReservedRoute($slug)) {
        setFlash('danger', 'Please provide a valid slug.');
        redirect('news-edit.php?id=' . $id);
    }

    $existing = $pdo->prepare('SELECT id FROM news WHERE slug = :slug AND id != :id LIMIT 1');
    $existing->execute([':slug' => $slug, ':id' => $id]);
    if ($existing->fetch()) {
        setFlash('danger', 'This slug is already in use. Please choose another slug.');
        redirect('news-edit.php?id=' . $id);
    }

    $update = $pdo->prepare('UPDATE news SET title = :title, slug = :slug, content = :content, category_id = :category_id, is_anonymous = :is_anonymous, status = :status, updated_at = NOW() WHERE id = :id');
    $update->execute([
        ':title' => $title,
        ':slug' => $slug,
        ':content' => $content,
        ':category_id' => $categoryId,
        ':is_anonymous' => $anonymous,
        ':status' => $status,
        ':id' => $id,
    ]);

    setFlash('success', 'News updated successfully.');
    redirect('news.php');
}

$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit News | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Edit News</h2>
            <a href="news.php" class="btn btn-outline-secondary">Back</a>
        </div>
        <?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <div class="col-md-12">
                        <label class="form-label">Title</label>
                        <input class="form-control" type="text" name="title" value="<?= e((string) ($item['title'] ?? '')) ?>" required maxlength="255">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Slug</label>
                        <input class="form-control" type="text" name="slug" value="<?= e((string) ($item['slug'] ?? '')) ?>" maxlength="120" pattern="[a-z0-9-]+">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= ((int) $category['id'] === (int) $item['category_id']) ? 'selected' : '' ?>><?= e((string) $category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="pending" <?= ($item['status'] === 'pending' ? 'selected' : '') ?>>Pending</option>
                            <option value="published" <?= ($item['status'] === 'published' ? 'selected' : '') ?>>Published</option>
                            <option value="draft" <?= ($item['status'] === 'draft' ? 'selected' : '') ?>>Draft</option>
                            <option value="archived" <?= ($item['status'] === 'archived' ? 'selected' : '') ?>>Archived</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_anonymous" value="1" id="is_anonymous" <?= ((int) $item['is_anonymous'] === 1 ? 'checked' : '') ?>>
                            <label class="form-check-label" for="is_anonymous">Anonymous author</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Content</label>
                        <textarea class="form-control" name="content" rows="8" required><?= e((string) ($item['content'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Update Article</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
