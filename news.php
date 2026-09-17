<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); exit('News item not found.'); }
$pdo = Database::getInstance();
$stmt = $pdo->prepare("SELECT n.*, c.name AS category_name, u.username AS author_username FROM news n LEFT JOIN categories c ON c.id=n.category_id LEFT JOIN users u ON u.id=n.author_id WHERE n.id=:id AND n.status='published' LIMIT 1");
$stmt->execute([':id'=>$id]);
$item = $stmt->fetch();
if (!$item) { http_response_code(404); exit('News item not found.'); }
$pdo->prepare('UPDATE news SET views=views+1 WHERE id=:id')->execute([':id'=>$id]);
$siteName = getSetting('site_name', APP_NAME);
$author = (int)$item['is_anonymous'] === 1 ? 'Anonymous' : (string)($item['author_username'] ?? 'Member');
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e((string)$item['title']) ?> | <?= e($siteName) ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"></head><body><header class="site-header"><div class="container py-3"><a class="brand" href="index.php"><span><?= e($siteName) ?></span></a></div></header><main class="container article-layout py-4 py-lg-5"><a href="index.php" class="back-link">← Back to latest news</a><article class="article"><span class="story-category"><?= e((string)($item['category_name'] ?? 'News')) ?></span><h1><?= e((string)$item['title']) ?></h1><div class="article-meta">By <?= e($author) ?> · <?= e(date('M d, Y', strtotime((string)$item['created_at']))) ?> · <?= (int)$item['views'] + 1 ?> views</div><?php if (!empty($item['image'])): ?><img class="article-image" src="<?= e((string)$item['image']) ?>" alt="<?= e((string)$item['title']) ?>"><?php endif; ?><div class="article-content"><?= nl2br(e((string)$item['content'])) ?></div></article></main></body></html>
