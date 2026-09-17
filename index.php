<?php

declare(strict_types=1);

session_start();

if (!file_exists(__DIR__ . '/config.php')) {
    header('Location: install.php');
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$siteName = getSetting('site_name', APP_NAME);
$breakingNews = getSetting('breaking_news', 'Latest technology and business updates');
$headerText = getSetting('header_text', 'Breaking stories, trusted reporting.');
$footerCopyright = getSetting('footer_copyright', '© ' . date('Y') . ' Gadget 50. All rights reserved.');

try {
    $pdo = Database::getInstance();
    $menusStmt = $pdo->prepare('SELECT * FROM menus WHERE status = :status ORDER BY position, sort_order ASC');
    $menusStmt->execute([':status' => 'active']);
    $menus = $menusStmt->fetchAll();

    $categoryFilter = trim((string) ($_GET['category'] ?? ''));
    $newsSql = 'SELECT n.*, c.name AS category_name, u.username AS author_username
        FROM news n
        LEFT JOIN categories c ON c.id = n.category_id
        LEFT JOIN users u ON u.id = n.author_id
        WHERE n.status = :status';
    $params = [':status' => 'published'];

    if ($categoryFilter !== '') {
        $newsSql .= ' AND c.slug = :slug';
        $params[':slug'] = $categoryFilter;
    }

    $newsSql .= ' ORDER BY n.created_at DESC';
    $newsStmt = $pdo->prepare($newsSql);
    $newsStmt->execute($params);
    $news = $newsStmt->fetchAll();

    $categoriesStmt = $pdo->query('SELECT * FROM categories WHERE status = "active" ORDER BY name ASC');
    $categories = $categoriesStmt->fetchAll();
} catch (Throwable $e) {
    $menus = [];
    $news = [];
    $categories = [];
}

$user = currentUser();
$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="topbar">
        <div class="container d-flex justify-content-between align-items-center py-2 text-white small flex-wrap gap-2">
            <span><?= htmlspecialchars($headerText, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="fw-semibold"><?= htmlspecialchars($breakingNews, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top border-bottom shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php foreach ($menus as $menu): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= htmlspecialchars($menu['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($menu['title'], ENT_QUOTES, 'UTF-8') ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <?php if ($user): ?>
                        <span class="small text-muted">Hi, <?= htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($user['role'] === 'super_admin'): ?>
                            <a class="btn btn-outline-primary btn-sm" href="admin/index.php">Admin</a>
                        <?php endif; ?>
                        <a class="btn btn-primary btn-sm" href="submit.php">Submit News</a>
                        <a class="btn btn-outline-secondary btn-sm" href="logout.php">Logout</a>
                    <?php else: ?>
                        <a class="btn btn-outline-primary btn-sm" href="login.php">Login</a>
                        <a class="btn btn-primary btn-sm" href="register.php">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>" role="alert"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['installed']) && $_GET['installed'] === '1'): ?>
            <div class="alert alert-success">Installation successful. Welcome to Gadget 50.</div>
        <?php endif; ?>

        <section class="mb-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <p class="text-uppercase text-primary fw-bold mb-2">Featured</p>
                    <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="lead text-muted">A modern public news platform built with pure native PHP and MySQL for secure, scalable publishing.</p>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="card border-0 shadow-sm bg-light p-4">
                        <div class="display-6 fw-bold text-primary">Live</div>
                        <div class="small text-secondary">Public News Portal</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-4">
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-primary" href="index.php">All</a>
                <?php foreach ($categories as $category): ?>
                    <a class="btn btn-sm btn-outline-primary" href="index.php?category=<?= urlencode((string) $category['slug']) ?>"><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="row g-4">
            <?php if (!empty($news)): ?>
                <?php foreach ($news as $item): ?>
                    <div class="col-md-6 col-lg-4">
                        <article class="card h-100 shadow-sm border-0 news-card">
                            <div class="card-body">
                                <span class="badge text-bg-primary mb-2"><?= htmlspecialchars((string) ($item['category_name'] ?? 'General'), ENT_QUOTES, 'UTF-8') ?></span>
                                <h5 class="card-title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars(substr(strip_tags((string) $item['content']), 0, 120), ENT_QUOTES, 'UTF-8') ?>...</p>
                            </div>
                            <div class="card-footer bg-white border-0 d-flex justify-content-between flex-wrap align-items-center">
                                <small class="text-muted">
                                    <?php if ((int) $item['is_anonymous'] === 1): ?>
                                        By Anonymous
                                    <?php else: ?>
                                        By <?= htmlspecialchars((string) ($item['author_username'] ?? 'Member'), ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </small>
                                <small class="text-muted"><?= htmlspecialchars(date('M d, Y', strtotime((string) $item['created_at'])), ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No published news has been added yet. The installation phase is complete and the admin panel is ready for content management.</div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="mt-5 bg-dark text-white">
        <div class="container py-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><?= htmlspecialchars($footerCopyright, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="small text-light-emphasis">Powered by Gadget 50</div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
