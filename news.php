<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('News item not found.');
}

$pdo = Database::getInstance();
$stmt = $pdo->prepare("SELECT n.*, c.name AS category_name, u.username AS author_username FROM news n LEFT JOIN categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id WHERE n.id = :id AND n.status = 'published' LIMIT 1");
$stmt->execute([':id' => $id]);
$item = $stmt->fetch();
if (!$item) {
    http_response_code(404);
    exit('News item not found.');
}

$pdo->prepare('UPDATE news SET views = views + 1 WHERE id = :id')->execute([':id' => $id]);
$siteName = getSetting('site_name', APP_NAME);
$author = (int) $item['is_anonymous'] === 1 ? 'Anonymous' : (string) ($item['author_username'] ?? 'Member');
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= e((string) $item['title']) ?> | <?= e($siteName) ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"></head>
<body><main class="container py-5"><a href="index.php" class="btn btn-outline-secondary mb-4">Back to news</a><article class="card border-0 shadow-sm"><div class="card-body p-4 p-lg-5"><div class="text-primary fw-semibold mb-2"><?= e((string) ($item['category_name'] ?? 'General')) ?></div><h1><?= e((string) $item['title']) ?></h1><p class="text-muted">By <?= e($author) ?> · <?= e(date('M d, Y', strtotime((string) $item['created_at']))) ?> · <?= (int) $item['views'] + 1 ?> views</p><hr><div class="news-content"><?= nl2br(e((string) $item['content'])) ?></div></div></article></main></body></html>
