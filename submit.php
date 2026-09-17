<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
requireLogin('login.php');

$user = currentUser();
$pdo = Database::getInstance();
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    try {
        if ($title === '' || strlen($title) > 255 || $content === '' || $categoryId <= 0) throw new RuntimeException('Title, category, and content are required.');
        $check = $pdo->prepare("SELECT id FROM categories WHERE id = :id AND status = 'active'");
        $check->execute([':id' => $categoryId]);
        if (!$check->fetch()) throw new RuntimeException('Please select an active category.');
        $image = uploadNewsImage($_FILES['image'] ?? []);
        $stmt = $pdo->prepare('INSERT INTO news (title, slug, content, image, category_id, author_id, author_name, is_anonymous, status, created_at) VALUES (:title, :slug, :content, :image, :category_id, :author_id, :author_name, :is_anonymous, :status, NOW())');
        $stmt->execute([':title'=>$title, ':slug'=>slugify($title), ':content'=>$content, ':image'=>$image, ':category_id'=>$categoryId, ':author_id'=>(int)$user['id'], ':author_name'=>(string)$user['username'], ':is_anonymous'=>$anonymous, ':status'=>'pending']);
        setFlash('success', 'Your news has been submitted for moderation.');
        redirect('dashboard.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
$flash = getFlash();
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Write News | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"></head><body class="bg-light"><main class="container py-4"><div class="row g-4"><aside class="col-lg-3"><div class="dashboard-sidebar p-3"><h4 class="text-white">Gadget 50</h4><p class="text-white-50 small">Contributor workspace</p><nav class="nav flex-column gap-1"><a href="dashboard.php">Dashboard</a><a class="active" href="submit.php">Write News</a><a href="index.php">Public Site</a><a href="logout.php">Logout</a></nav></div></aside><section class="col-lg-9"><div class="d-flex justify-content-between align-items-center mb-4"><div><h2>Write a News Story</h2><p class="text-muted mb-0">Share a story with the Gadget 50 community.</p></div><a href="dashboard.php" class="btn btn-outline-secondary">My Dashboard</a></div><?php if ($flash): ?><div class="alert alert-<?= e((string)$flash['type']) ?>"><?= e((string)$flash['message']) ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><div class="card border-0 shadow-sm"><div class="card-body p-4"><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="mb-3"><label class="form-label">Headline</label><input name="title" maxlength="255" class="form-control form-control-lg" required></div><div class="mb-3"><label class="form-label">Category</label><select name="category_id" class="form-select" required><option value="">Choose a category</option><?php foreach($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>"><?= e((string)$cat['name']) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label">Featured Image</label><input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif"><div class="form-text">JPG, PNG, WEBP or GIF, maximum 5 MB.</div></div><div class="mb-3"><label class="form-label">Story Content</label><textarea name="content" class="form-control" rows="12" required></textarea></div><div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="is_anonymous" id="is_anonymous" value="1"><label class="form-check-label" for="is_anonymous">Publish the author publicly as Anonymous</label></div><button class="btn btn-primary btn-lg">Submit for Review</button></form></div></div></section></div></main></body></html>
