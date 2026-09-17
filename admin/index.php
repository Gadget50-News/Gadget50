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
];

$recentNews = $pdo->query(
    'SELECT n.id, n.title, n.status, n.is_anonymous, n.created_at,
            u.username, c.name AS category_name
     FROM news n
     LEFT JOIN users u ON u.id = n.author_id
     LEFT JOIN categories c ON c.id = n.category_id
     ORDER BY n.created_at DESC
     LIMIT 8'
)->fetchAll();
$flash = getFlash();
$siteName = getSetting('site_name', APP_NAME);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | <?= e($siteName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #111827; }
        .sidebar a { color: rgba(255,255,255,.8); text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background: rgba(255,255,255,.12); }
        .sidebar a { padding: .55rem .7rem; border-radius: .5rem; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-2 sidebar p-3">
            <h4 class="text-white mb-4"><?= e($siteName) ?></h4>
            <nav class="nav flex-column gap-1">
                <a class="active" href="index.php">Dashboard</a>
                <a href="../submit.php">➕ Post News</a>
                <a href="news.php">Manage News</a>
                <a href="settings.php">Site Settings</a>
                <a href="users.php">Users</a>
                <a href="categories.php">Categories</a>
                <a href="menus.php">Menus</a>
                <a href="../index.php">Public Site</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </aside>

        <main class="col-md-10 p-4 bg-light min-vh-100">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div>
                    <h2 class="mb-1">Super Admin Dashboard</h2>
                    <p class="text-muted mb-0">Manage publishing, moderation, users, and site configuration.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="../submit.php" class="btn btn-success">➕ Post New News</a>
                    <a href="news.php" class="btn btn-primary">Moderate News</a>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <?php foreach ([
                    ['Users', $stats['users'], 'primary'],
                    ['Total News', $stats['news'], 'dark'],
                    ['Pending Review', $stats['pending'], 'warning'],
                    ['Published', $stats['published'], 'success'],
                ] as [$label, $value, $color]): ?>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100"><div class="card-body">
                            <div class="text-muted"><?= e($label) ?></div>
                            <div class="display-6 fw-bold text-<?= e($color) ?>"><?= (int) $value ?></div>
                        </div></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Recent Submissions</h4>
                        <a href="news.php" class="btn btn-sm btn-outline-primary">View All News</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php if (!$recentNews): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No news submissions yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentNews as $item): ?>
                                    <tr>
                                        <td><?= e((string) $item['title']) ?></td>
                                        <td><?= (int) $item['is_anonymous'] === 1 ? 'Anonymous' : e((string) ($item['username'] ?? 'Unknown')) ?></td>
                                        <td><?= e((string) ($item['category_name'] ?? 'General')) ?></td>
                                        <td><span class="badge text-bg-secondary"><?= e((string) $item['status']) ?></span></td>
                                        <td><a class="btn btn-sm btn-outline-primary" href="news-edit.php?id=<?= (int) $item['id'] ?>">Edit</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
