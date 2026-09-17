<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

requireLogin('../login.php');
requireRole('super_admin', '../login.php');

$pdo = Database::getInstance();
$stats = [
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'news' => (int) $pdo->query('SELECT COUNT(*) FROM news')->fetchColumn(),
    'pending' => (int) $pdo->query('SELECT COUNT(*) FROM news WHERE status = "pending"')->fetchColumn(),
    'published' => (int) $pdo->query('SELECT COUNT(*) FROM news WHERE status = "published"')->fetchColumn(),
];

$recentNews = $pdo->query(
    'SELECT n.*, u.username, c.name AS category_name FROM news n LEFT JOIN users u ON u.id = n.author_id LEFT JOIN categories c ON c.id = n.category_id ORDER BY n.created_at DESC LIMIT 5'
)->fetchAll();
$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Gadget 50</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #111827; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; }
        .sidebar a:hover { color: white; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <aside class="col-md-2 sidebar p-3">
                <h4 class="text-white">Gadget 50</h4>
                <nav class="nav flex-column gap-2 mt-4">
                    <a href="index.php">Dashboard</a>
                    <a href="settings.php">Site Settings</a>
                    <a href="news.php">News</a>
                    <a href="users.php">Users</a>
                    <a href="categories.php">Categories</a>
                    <a href="menus.php">Menus</a>
                    <a href="../index.php">Public Site</a>
                    <a href="../logout.php">Logout</a>
                </nav>
            </aside>
            <main class="col-md-10 p-4 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Admin Dashboard</h2>
                    <a href="../index.php" class="btn btn-primary">View Site</a>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted">Users</div>
                                <h3><?= (int) $stats['users'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted">Total News</div>
                                <h3><?= (int) $stats['news'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted">Pending</div>
                                <h3><?= (int) $stats['pending'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="text-muted">Published</div>
                                <h3><?= (int) $stats['published'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h4 class="mb-3">Recent News</h4>
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentNews as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($item['username'] ?? 'Anonymous'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) ($item['category_name'] ?? 'General'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="badge text-bg-secondary"><?= htmlspecialchars((string) $item['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
