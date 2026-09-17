<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin('login.php');

$user = currentUser();
$pdo = Database::getInstance();
$mine = $pdo->prepare('SELECT n.id, n.title, n.status, n.is_anonymous, n.image, n.created_at, c.name AS category_name FROM news n LEFT JOIN categories c ON c.id = n.category_id WHERE n.author_id = :author ORDER BY n.created_at DESC');
$mine->execute([':author' => (int) $user['id']]);
$posts = $mine->fetchAll();

$siteName = getSetting('site_name', APP_NAME);
$pending = count(array_filter($posts, fn ($post) => (string) $post['status'] === 'pending'));
$published = count(array_filter($posts, fn ($post) => (string) $post['status'] === 'published'));
$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My newsroom | <?= e($siteName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="dashboard.php"><?= e($siteName) ?><small>MY NEWSROOM</small></a>
        <nav>
            <a class="active" href="dashboard.php">▦ Overview</a>
            <a href="submit.php">✎ Write story</a>
            <a href="index.php">↗ Public site</a>
        </nav>
        <a class="admin-logout" href="logout.php">Sign out</a>
    </aside>

    <main class="admin-main">
        <div class="mobile-admin-head">
            <span><?= e($siteName) ?></span>
            <a href="index.php">Public site</a>
        </div>

        <div class="admin-top">
            <div>
                <span class="eyebrow">MY NEWSROOM</span>
                <h1>Welcome back, <?= e((string) $user['username']) ?>.</h1>
                <p>Track your stories and their editorial status.</p>
            </div>
            <a class="btn btn-primary" href="submit.php">＋ Write a story</a>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-4">
                <div class="metric-card">
                    <span>Your stories</span>
                    <strong class="metric-blue"><?= count($posts) ?></strong>
                    <small>Keep publishing</small>
                </div>
            </div>
            <div class="col-6 col-xl-4">
                <div class="metric-card">
                    <span>Pending</span>
                    <strong class="metric-orange"><?= $pending ?></strong>
                    <small>Awaiting review</small>
                </div>
            </div>
            <div class="col-6 col-xl-4">
                <div class="metric-card">
                    <span>Published</span>
                    <strong class="metric-green"><?= $published ?></strong>
                    <small>Live on the site</small>
                </div>
            </div>
        </div>

        <div class="content-panel">
            <div class="panel-heading">
                <div>
                    <span class="eyebrow">YOUR WORK</span>
                    <h2>Submitted stories</h2>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table newsroom-table align-middle">
                    <thead>
                        <tr>
                            <th>Story</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$posts): ?>
                        <tr><td colspan="4" class="text-center text-muted py-5">You have not submitted a story yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <div class="story-cell">
                                    <?php if (!empty($post['image'])): ?>
                                        <img src="<?= e((string) $post['image']) ?>" alt="">
                                    <?php else: ?>
                                        <div class="mini-placeholder">N</div>
                                    <?php endif; ?>
                                    <a href="news.php?id=<?= (int) $post['id'] ?>"><?= e((string) $post['title']) ?></a>
                                </div>
                            </td>
                            <td><?= e((string) ($post['category_name'] ?? 'General')) ?></td>
                            <td><span class="status status-<?= e((string) $post['status']) ?>"><?= e(ucfirst((string) $post['status'])) ?></span></td>
                            <td><?= e(date('M d, Y', strtotime((string) $post['created_at']))) ?></td>
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
