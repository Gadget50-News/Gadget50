<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
requireLogin('login.php');

$user = currentUser();
$pdo = Database::getInstance();
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    if ($title === '' || strlen($title) > 255 || $content === '' || $categoryId <= 0) {
        setFlash('danger', 'A valid title, category, and content are required.');
        redirect('submit.php');
    }

    $categoryCheck = $pdo->prepare("SELECT id FROM categories WHERE id = :id AND status = 'active'");
    $categoryCheck->execute([':id' => $categoryId]);
    if (!$categoryCheck->fetch()) {
        setFlash('danger', 'Please choose an active category.');
        redirect('submit.php');
    }

    $stmt = $pdo->prepare('INSERT INTO news (title, slug, content, category_id, author_id, author_name, is_anonymous, status, created_at) VALUES (:title, :slug, :content, :category_id, :author_id, :author_name, :is_anonymous, :status, NOW())');
    $stmt->execute([':title' => $title, ':slug' => slugify($title), ':content' => $content, ':category_id' => $categoryId, ':author_id' => (int) $user['id'], ':author_name' => (string) $user['username'], ':is_anonymous' => $anonymous, ':status' => 'pending']);
    setFlash('success', 'Your news submission has been received and is pending moderation.');
    redirect('index.php');
}

$flash = getFlash();
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Submit News | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><main class="container py-5"><div class="row justify-content-center"><div class="col-lg-8"><div class="card border-0 shadow-sm"><div class="card-body p-4"><div class="d-flex justify-content-between mb-3"><h2>Submit News</h2><a href="index.php" class="btn btn-outline-secondary btn-sm">Back</a></div><?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="mb-3"><label class="form-label">Title</label><input name="title" maxlength="255" class="form-control" required></div><div class="mb-3"><label class="form-label">Category</label><select name="category_id" class="form-select" required><option value="">Select category</option><?php foreach ($categories as $cat): ?><option value="<?= (int) $cat['id'] ?>"><?= e((string) $cat['name']) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="8" required></textarea></div><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_anonymous" id="is_anonymous" value="1"><label class="form-check-label" for="is_anonymous">Post Anonymously</label></div><button class="btn btn-primary">Submit for Review</button></form></div></div></div></div></main></body></html>
