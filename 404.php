<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$pdo = Database::getInstance();
$siteName = getSetting('site_name', APP_NAME);
$slug = trim((string) ($_GET['slug'] ?? ''));
$id = (int) ($_GET['id'] ?? 0);

if ($slug !== '') {
    $stmt = $pdo->prepare('SELECT n.*, c.name AS category_name, u.username AS author_username FROM news n LEFT JOIN categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id WHERE n.slug = :slug AND n.status = :status LIMIT 1');
    $stmt->execute([':slug' => $slug, ':status' => 'published']);
} else {
    $stmt = $pdo->prepare('SELECT n.*, c.name AS category_name, u.username AS author_username FROM news n LEFT JOIN categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id WHERE n.id = :id AND n.status = :status LIMIT 1');
    $stmt->execute([':id' => $id, ':status' => 'published']);
}

$item = $stmt->fetch();
if (!$item) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$pdo->prepare('UPDATE news SET views = views + 1 WHERE id = :id')->execute([':id' => (int) $item['id']]);
$author = ((int) $item['is_anonymous'] === 1) ? 'Anonymous' : (string) ($item['author_username'] ?? 'Member');
$related = $pdo->query("SELECT n.id, n.title, n.slug, n.image, n.created_at, c.name AS category_name FROM news n LEFT JOIN categories c ON c.id = n.category_id WHERE n.status = 'published' AND n.id != " . (int) $item['id'] . " ORDER BY n.created_at DESC LIMIT 3")->fetchAll();
$mostRead = $pdo->query("SELECT n.id, n.title, n.slug, c.name AS category_name FROM news n LEFT JOIN categories c ON c.id = n.category_id WHERE n.status = 'published' ORDER BY n.views DESC, n.created_at DESC LIMIT 5")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string) $item['title']) ?> | <?= e($siteName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="story-body">
<header class="site-header">
    <div class="container py-3">
        <a class="brand" href="/index.php"><span><?= e($siteName) ?></span></a>
    </div>
</header>

<main class="container article-layout py-4 py-lg-5">
    <a href="/index.php" class="back-link">← Back to latest news</a>

    <article class="article">
        <span class="story-category"><?= e((string) ($item['category_name'] ?? 'News')) ?></span>
        <h1><?= e((string) $item['title']) ?></h1>
        <div class="article-meta">By <?= e($author) ?> · <?= e(date('M d, Y', strtotime((string) $item['created_at']))) ?> · <?= (int) $item['views'] ?> views</div>

        <?php if (!empty($item['image'])): ?>
            <img class="article-image" src="/<?= e((string) $item['image']) ?>" alt="<?= e((string) $item['title']) ?>">
        <?php endif; ?>

        <div class="article-content"><?= nl2br(e((string) $item['content'])) ?></div>
    </article>

    <div class="row g-4 mt-4">
        <div class="col-lg-8">
            <div class="sidebar-box">
                <div class="panel-header mb-3"><h3>Related stories</h3></div>
                <div class="row g-3">
                    <?php foreach ($related as $story): ?>
                        <div class="col-md-6">
                            <article class="mini-story">
                                <a href="/news/<?= e((string) ($story['slug'] ?? '')) ?>">
                                    <?php if (!empty($story['image'])): ?>
                                        <img src="/<?= e((string) $story['image']) ?>" alt="<?= e((string) $story['title']) ?>">
                                    <?php else: ?>
                                        <div class="mini-story-placeholder">G50</div>
                                    <?php endif; ?>
                                </a>
                                <h4><a href="/news/<?= e((string) ($story['slug'] ?? '')) ?>"><?= e((string) $story['title']) ?></a></h4>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <aside class="col-lg-4">
            <div class="sidebar-box">
                <div class="panel-header mb-3"><h3>Most read</h3></div>
                <?php foreach ($mostRead as $story): ?>
                    <article class="side-story">
                        <div class="side-story-meta"><?= e((string) ($story['category_name'] ?? 'News')) ?></div>
                        <h4><a href="/news/<?= e((string) ($story['slug'] ?? '')) ?>"><?= e((string) $story['title']) ?></a></h4>
                    </article>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>
</main>
</body>
</html>
