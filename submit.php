<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user = currentUser();
$flash = getFlash();

$pdo = Database::getInstance();
$catsStmt = $pdo->query('SELECT * FROM categories WHERE status = "active" ORDER BY name ASC');
$categories = $catsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $anonym = isset($_POST['is_anonymous']) ? 1 : 0;

    if ($title === '' || $content === '' || $categoryId <= 0) {
        setFlash('danger', 'Title, category, and content are required.');
        redirect('submit.php');
    }

    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    $slug = trim((string) $slug, '-');

    $stmt = $pdo->prepare(
        'INSERT INTO news (title, slug, content, category_id, author_id, author_name, is_anonymous, status, created_at) VALUES (:title, :slug, :content, :category_id, :author_id, :author_name, :is_anonymous, :status, NOW())'
    );

    $stmt->execute([
        ':title' => $title,
        ':slug' => $slug !== '' ? $slug : 'news-item',
        ':content' => $content,
        ':category_id' => $categoryId,
        ':author_id' => (int) $user['id'],
        ':author_name' => (string) $user['username'],
        ':is_anonymous' => $anonym,
        ':status' => 'pending',
    ]);

    setFlash('success', 'Your news submission has been received and is pending moderation.');
    redirect('index.php');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit News | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="mb-0">Submit News</h2>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">Back</a>
                        </div>

                        <?php if ($flash): ?>
                            <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars((string) $cat['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Content</label>
                                <textarea name="content" class="form-control" rows="8" required></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="is_anonymous" value="1" id="is_anonymous">
                                <label class="form-check-label" for="is_anonymous">Post Anonymously</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit for Review</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
