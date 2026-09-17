<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
requireLogin('login.php');

$user = currentUser();
$pdo = Database::getInstance();
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$error = null;
$slugValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $slugValue = trim((string) ($_POST['slug'] ?? ''));
    $anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    try {
        if ($title === '' || strlen($title) > 255 || $content === '' || $categoryId <= 0) {
            throw new RuntimeException('Title, category, and content are required.');
        }

        $check = $pdo->prepare("SELECT id FROM categories WHERE id = :id AND status = 'active'");
        $check->execute([':id' => $categoryId]);
        if (!$check->fetch()) {
            throw new RuntimeException('Please select an active category.');
        }

        $slug = $slugValue !== '' ? sanitizeSlug($slugValue) : sanitizeSlug($title);
        if ($slug === '' || isReservedRoute($slug)) {
            throw new RuntimeException('Please provide a valid slug.');
        }

        $slug = generateUniqueSlug($pdo, 'news', 'slug', $slug);
        $image = uploadNewsImage($_FILES['image'] ?? []);

        $stmt = $pdo->prepare('INSERT INTO news (title, slug, content, image, category_id, author_id, author_name, is_anonymous, status, created_at) VALUES (:title, :slug, :content, :image, :category_id, :author_id, :author_name, :is_anonymous, :status, NOW())');
        $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':content' => $content,
            ':image' => $image,
            ':category_id' => $categoryId,
            ':author_id' => (int) $user['id'],
            ':author_name' => (string) ($user['username'] ?? ''),
            ':is_anonymous' => $anonymous,
            ':status' => 'pending',
        ]);

        setFlash('success', 'Your news has been submitted for moderation.');
        redirect('dashboard.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Write News | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="mb-3">Write a story</h2>
                        <?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
                        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                        <form method="post" enctype="multipart/form-data" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input class="form-control" type="text" name="title" value="<?= e((string) ($_POST['title'] ?? '')) ?>" maxlength="255" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Slug</label>
                                <input class="form-control" type="text" name="slug" value="<?= e($slugValue) ?>" maxlength="120" placeholder="my-first-news" pattern="[a-z0-9-]+">
                                <div class="form-text">URL preview: /news/<span id="slug-preview"><?= e($slugValue !== '' ? $slugValue : 'my-first-news') ?></span></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" required>
                                    <option value="">Select category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= (int) $category['id'] ?>" <?= ((int) ($_POST['category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>><?= e((string) $category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Image</label>
                                <input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Content</label>
                                <textarea class="form-control" name="content" rows="8" required><?= e((string) ($_POST['content'] ?? '')) ?></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="is_anonymous" value="1" id="is_anonymous">
                                <label class="form-check-label" for="is_anonymous">Submit anonymously</label>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                                <button class="btn btn-primary" type="submit">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
        const slugInput = document.querySelector('input[name="slug"]');
        const preview = document.getElementById('slug-preview');
        if (slugInput && preview) {
            slugInput.addEventListener('input', function () {
                const value = this.value.trim() || 'my-first-news';
                preview.textContent = value.replace(/[^a-z0-9-]/gi, '-').toLowerCase();
            });
        }
    </script>
</body>
</html>
