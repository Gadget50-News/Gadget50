<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$siteName = getSetting('site_name', APP_NAME);
$logo = getSetting('site_logo');
$favicon = getSetting('site_favicon');
$headerText = getSetting('header_text', 'Breaking stories, trusted reporting.');
$breakingNews = getSetting('breaking_news', 'Latest technology and business updates');
$footerCopyright = getSetting('footer_copyright', '© ' . date('Y') . ' ' . $siteName . '. All rights reserved.');

$pdo = Database::getInstance();
$menus = $pdo->query("SELECT * FROM menus WHERE status = 'active' ORDER BY position, sort_order ASC")->fetchAll();
$categories = $pdo->query("SELECT id, name, slug FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$user = currentUser();
$flash = getFlash();

$lead = $pdo->query("SELECT n.*, c.name AS category_name, u.username AS author_username FROM news n LEFT JOIN categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id WHERE n.status = 'published' ORDER BY n.created_at DESC LIMIT 1")->fetch();
$latestStories = $pdo->query("SELECT n.*, c.name AS category_name, u.username AS author_username FROM news n LEFT JOIN categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id WHERE n.status = 'published' ORDER BY n.created_at DESC LIMIT 7")->fetchAll();
$mostRead = $pdo->query("SELECT n.*, c.name AS category_name FROM news n LEFT JOIN categories c ON c.id = n.category_id WHERE n.status = 'published' ORDER BY n.views DESC, n.created_at DESC LIMIT 4")->fetchAll();

$tickerItems = $pdo->query("SELECT title FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 8")->fetchAll();
$categoryBlocks = [];
foreach ($categories as $category) {
    $stmt = $pdo->prepare("SELECT n.id, n.title, n.image, n.excerpt, n.created_at, c.name AS category_name FROM news n LEFT JOIN categories c ON c.id = n.category_id WHERE n.status = 'published' AND c.id = :category_id ORDER BY n.created_at DESC LIMIT 3");
    $stmt->execute([':category_id' => (int) $category['id']]);
    $categoryBlocks[] = [
        'name' => $category['name'],
        'slug' => $category['slug'],
        'items' => $stmt->fetchAll(),
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($siteName) ?></title>
    <?php if ($favicon !== ''): ?><link rel="icon" href="<?= e($favicon) ?>"><?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="breaking-bar">
    <div class="container breaking-inner">
        <span class="breaking-label">BREAKING</span>
        <div class="ticker-track">
            <span><?= e($breakingNews) ?></span>
            <?php foreach ($tickerItems as $item): ?>
                <span>• <?= e((string) $item['title']) ?></span>
            <?php endforeach; ?>
            <?php foreach ($tickerItems as $item): ?>
                <span>• <?= e((string) $item['title']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<header class="site-header">
    <div class="container py-3 d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <a class="brand" href="index.php">
            <?php if ($logo !== ''): ?><img src="<?= e($logo) ?>" alt="<?= e($siteName) ?>"><?php endif; ?>
            <span><?= e($siteName) ?></span>
        </a>
        <div class="header-actions">
            <?php if ($user): ?>
                <a class="btn btn-light btn-sm" href="dashboard.php">My newsroom</a>
                <?php if (($user['role'] ?? '') === 'super_admin'): ?>
                    <a class="btn btn-warning btn-sm" href="admin/index.php">Admin</a>
                <?php endif; ?>
                <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="btn btn-outline-light btn-sm" href="login.php">Login</a>
                <a class="btn btn-warning btn-sm" href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
    <nav class="category-nav">
        <div class="container d-flex gap-4 overflow-auto">
            <a href="index.php">Home</a>
            <?php foreach ($categories as $cat): ?>
                <a href="index.php?category=<?= urlencode((string) $cat['slug']) ?>"><?= e((string) $cat['name']) ?></a>
            <?php endforeach; ?>
            <?php foreach ($menus as $menu): ?>
                <?php if ($menu['position'] === 'header' && (string) $menu['title'] !== 'Home'): ?>
                    <a href="<?= e((string) $menu['url']) ?>"><?= e((string) $menu['title']) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>
</header>

<main class="container py-4 py-lg-5">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
    <?php endif; ?>

    <div class="section-heading">
        <div>
            <span class="eyebrow"><?= e(strtoupper((string) $headerText)) ?></span>
            <h1>Top stories</h1>
        </div>
        <?php if ($user): ?>
            <a class="btn btn-primary" href="submit.php">✎ Write a story</a>
        <?php endif; ?>
    </div>

    <?php if ($lead): ?>
        <section class="lead-story mb-4">
            <div class="row g-0">
                <div class="col-lg-7">
                    <?php if (!empty($lead['image'])): ?>
                        <img class="lead-image" loading="eager" src="<?= e((string) $lead['image']) ?>" alt="<?= e((string) $lead['title']) ?>">
                    <?php else: ?>
                        <div class="lead-image placeholder-image">NEWSROOM</div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-5 p-4 p-lg-5 d-flex flex-column justify-content-center">
                    <span class="story-category"><?= e((string) ($lead['category_name'] ?? 'News')) ?></span>
                    <h2><a href="news.php?id=<?= (int) $lead['id'] ?>"><?= e((string) $lead['title']) ?></a></h2>
                    <p class="text-muted"><?= e(substr(strip_tags((string) $lead['content']), 0, 220)) ?>…</p>
                    <a class="read-more" href="news.php?id=<?= (int) $lead['id'] ?>">Read full story →</a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="row g-4 mb-5">
        <?php foreach (array_slice($latestStories, 1, 6) as $item): ?>
            <div class="col-md-6 col-xl-4">
                <article class="story-card h-100">
                    <a href="news.php?id=<?= (int) $item['id'] ?>">
                        <?php if (!empty($item['image'])): ?>
                            <img loading="lazy" src="<?= e((string) $item['image']) ?>" alt="<?= e((string) $item['title']) ?>">
                        <?php else: ?>
                            <div class="story-placeholder">G50</div>
                        <?php endif; ?>
                    </a>
                    <div class="p-3">
                        <span class="story-category"><?= e((string) ($item['category_name'] ?? 'News')) ?></span>
                        <h3><a href="news.php?id=<?= (int) $item['id'] ?>"><?= e((string) $item['title']) ?></a></h3>
                        <p><?= e(substr(strip_tags((string) $item['content']), 0, 120)) ?>…</p>
                        <small><?= e(date('M d, Y', strtotime((string) $item['created_at']))) ?> · <?= (int) $item['views'] ?> views</small>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="row g-4 mb-5">
        <div class="col-lg-8">
            <?php foreach ($categoryBlocks as $block): ?>
                <?php if (!empty($block['items'])): ?>
                    <div class="category-panel mb-4">
                        <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                            <h3><?= e((string) $block['name']) ?></h3>
                            <a href="index.php?category=<?= urlencode((string) $block['slug']) ?>">View all</a>
                        </div>
                        <div class="row g-3">
                            <?php foreach ($block['items'] as $item): ?>
                                <div class="col-md-4">
                                    <article class="mini-story">
                                        <a href="news.php?id=<?= (int) $item['id'] ?>">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?= e((string) $item['image']) ?>" alt="<?= e((string) $item['title']) ?>">
                                            <?php else: ?>
                                                <div class="mini-story-placeholder">G50</div>
                                            <?php endif; ?>
                                        </a>
                                        <h4><a href="news.php?id=<?= (int) $item['id'] ?>"><?= e((string) $item['title']) ?></a></h4>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <aside class="col-lg-4">
            <div class="sidebar-box">
                <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                    <h3>Most read</h3>
                </div>
                <?php foreach ($mostRead as $item): ?>
                    <article class="side-story">
                        <div class="side-story-meta"><?= e((string) ($item['category_name'] ?? 'News')) ?></div>
                        <h4><a href="news.php?id=<?= (int) $item['id'] ?>"><?= e((string) $item['title']) ?></a></h4>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($user): ?>
                <div class="sidebar-box mt-4">
                    <div class="panel-header mb-3">
                        <h3>Writer tools</h3>
                    </div>
                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary btn-sm" href="submit.php">Submit a story</a>
                        <a class="btn btn-outline-secondary btn-sm" href="dashboard.php">My dashboard</a>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
    </section>
</main>

<footer class="site-footer">
    <div class="container d-flex justify-content-between flex-wrap gap-3">
        <span><?= e($footerCopyright) ?></span>
        <span>
            <?php foreach ($menus as $menu): ?>
                <?php if ($menu['position'] === 'footer'): ?>
                    <a href="<?= e((string) $menu['url']) ?>"><?= e((string) $menu['title']) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </span>
    </div>
</footer>
</body>
</html>
