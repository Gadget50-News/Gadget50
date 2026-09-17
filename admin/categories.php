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
        $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => $id]);
        setFlash('success', 'Category deleted.');
    } elseif ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = slugify(trim((string) ($_POST['slug'] ?? '')) ?: $name);
        if ($name === '' || strlen($name) > 120) { setFlash('danger', 'Enter a category name of 120 characters or fewer.'); }
        else { try { $pdo->prepare('INSERT INTO categories (name, slug, description, status) VALUES (:name, :slug, :description, :status)')->execute([':name'=>$name, ':slug'=>$slug, ':description'=>trim((string)($_POST['description']??'')), ':status'=>'active']); setFlash('success','Category created.'); } catch (PDOException $e) { setFlash('danger','That category slug already exists.'); } }
    }
    redirect('categories.php');
}
$categories = $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll(); $flash = getFlash();
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Categories | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5"><div class="d-flex justify-content-between mb-4"><h2>Categories</h2><a href="index.php" class="btn btn-outline-secondary">Dashboard</a></div><?php if($flash): ?><div class="alert alert-<?= e((string)$flash['type']) ?>"><?= e((string)$flash['message']) ?></div><?php endif; ?><div class="card border-0 shadow-sm mb-4"><div class="card-body"><form method="post" class="row g-3"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="create"><div class="col-md-4"><label class="form-label">Name</label><input class="form-control" name="name" maxlength="120" required></div><div class="col-md-4"><label class="form-label">Slug</label><input class="form-control" name="slug" maxlength="120"></div><div class="col-md-4"><label class="form-label">Description</label><input class="form-control" name="description"></div><div class="col-12"><button class="btn btn-primary">Add Category</button></div></form></div></div><div class="card border-0 shadow-sm"><div class="card-body"><table class="table"><thead><tr><th>Name</th><th>Slug</th><th>Action</th></tr></thead><tbody><?php foreach($categories as $cat): ?><tr><td><?= e((string)$cat['name']) ?></td><td><?= e((string)$cat['slug']) ?></td><td><form method="post" onsubmit="return confirm('Delete this category?');"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div></div></div></body></html>
