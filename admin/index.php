<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$pdo = Database::getInstance();
$stats = [
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'news' => (int) $pdo->query('SELECT COUNT(*) FROM news')->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM news WHERE status = 'pending'")->fetchColumn(),
    'published' => (int) $pdo->query("SELECT COUNT(*) FROM news WHERE status = 'published'")->fetchColumn(),
    'views' => (int) $pdo->query('SELECT COALESCE(SUM(views), 0) FROM news')->fetchColumn(),
];
$recentNews = $pdo->query('SELECT n.id, n.title, n.status, n.image, n.created_at, u.username, c.name AS category_name FROM news n LEFT JOIN users u ON u.id = n.author_id LEFT JOIN categories c ON c.id = n.category_id ORDER BY n.created_at DESC LIMIT 8')->fetchAll();
$flash = getFlash();
$siteName = getSetting('site_name', APP_NAME);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?= e($siteName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="index.php"><?= e($siteName) ?><small>NEWSROOM CMS</small></a>
        <nav>
            <a class="active" href="index.php">▦ Overview</a>
            <a href="news.php">▤ Stories</a>
            <a href="categories.php">◈ Categories</a>
            <a href="users.php">♙ Users</a>
            <a href="settings.php">⚙ Settings</a>
            <a href="menus.php">☰ Navigation</a>
            <a href="../index.php">↗ Public site</a>
        </nav>
        <a class="admin-logout" href="../logout.php">Sign out</a>
    </aside>

    <main class="admin-main">
        <div class="mobile-admin-head">
            <span><?= e($siteName) ?></span>
            <a href="../index.php">Public site</a>
        </div>

        <div class="admin-top">
            <div>
                <span class="eyebrow">NEWSROOM CONTROL</span>
                <h1>Editorial overview</h1>
                <p>Manage your publication and keep the newsroom moving.</p>
            </div>
            <a class="btn btn-primary" href="../submit.php">＋ Publish a story</a>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="metric-card">
                    <span>Users</span>
                    <strong class="metric-blue"><?= (int) $stats['users'] ?></strong>
                    <small>Active readers</small>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="metric-card">
                    <span>Stories</span>
                    <strong class="metric-ink"><?= (int) $stats['news'] ?></strong>
                    <small>Total records</small>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="metric-card">
                    <span>Pending</span>
                    <strong class="metric-orange"><?= (int) $stats['pending'] ?></strong>
                    <small>Needs review</small>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="metric-card">
                    <span>Views</span>
                    <strong class="metric-green"><?= (int) $stats['views'] ?></strong>
                    <small>Audience reach</small>
                </div>
            </div>
        </div>

        <div class="content-panel">
            <div class="panel-heading">
                <div>
                    <span class="eyebrow">EDITORIAL QUEUE</span>
                    <h2>Recent stories</h2>
                </div>
                <a href="news.php">View all →</a>
            </div>
            <div class="table-responsive">
                <table class="table newsroom-table align-middle">
                    <thead>
                        <tr>
                            <th>Story</th>
                            <th>Author</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$recentNews): ?>
                        <tr><td colspan="5" class="text-center text-muted py-5">No stories have been submitted yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentNews as $item): ?>
                        <tr>
                            <td>
                                <div class="story-cell">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="../<?= e((string) $item['image']) ?>" alt="">
                                    <?php else: ?>
                                        <div class="mini-placeholder">N</div>
                                    <?php endif; ?>
                                    <a href="news-edit.php?id=<?= (int) $item['id'] ?>"><?= e((string) $item['title']) ?></a>
                                </div>
                            </td>
                            <td><?= e((string) ($item['username'] ?? 'Unknown')) ?></td>
                            <td><?= e((string) ($item['category_name'] ?? 'General')) ?></td>
                            <td><span class="status status-<?= e((string) $item['status']) ?>"><?= e(ucfirst((string) $item['status'])) ?></span></td>
                            <td><a class="table-action" href="news-edit.php?id=<?= (int) $item['id'] ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
