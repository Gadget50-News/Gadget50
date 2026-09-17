<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$siteName = getSetting('site_name', APP_NAME);
$logo = getSetting('site_logo');
$favicon = getSetting('site_favicon');
$breakingNews = getSetting('breaking_news', 'Latest technology and business updates');
$headerText = getSetting('header_text', 'Breaking stories, trusted reporting.');
$footerCopyright = getSetting('footer_copyright', '© ' . date('Y') . ' Gadget 50. All rights reserved.');
$pdo = Database::getInstance();
$menusStmt = $pdo->prepare("SELECT * FROM menus WHERE status = 'active' ORDER BY position, sort_order ASC");
$menusStmt->execute();
$menus = $menusStmt->fetchAll();
$category = trim((string) ($_GET['category'] ?? ''));
$sql = "SELECT n.*, c.name AS category_name, u.username AS author_username FROM news n LEFT JOIN categories c ON c.id = n.category_id LEFT JOIN users u ON u.id = n.author_id WHERE n.status = 'published'";
$params = [];
if ($category !== '') { $sql .= ' AND c.slug = :slug'; $params[':slug'] = $category; }
$sql .= ' ORDER BY n.created_at DESC LIMIT 30';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$news = $stmt->fetchAll();
$categories = $pdo->query("SELECT id, name, slug FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$user = currentUser();
$flash = getFlash();
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($siteName) ?></title><?php if ($favicon !== ''): ?><link rel="icon" href="<?= e($favicon) ?>"><?php endif; ?><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"></head><body>
<div class="topbar"><div class="container d-flex justify-content-between py-2 text-white small flex-wrap gap-2"><span><?= e($headerText) ?></span><span class="fw-semibold"><?= e($breakingNews) ?></span></div></div><nav class="navbar navbar-expand-lg bg-white sticky-top border-bottom shadow-sm"><div class="container"><a class="navbar-brand fw-bold" href="index.php"><?php if ($logo !== ''): ?><img src="<?= e($logo) ?>" alt="<?= e($siteName) ?>" height="32" class="me-2"><?php endif; ?><?= e($siteName) ?></a><button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="mainNav"><ul class="navbar-nav me-auto"><?php foreach ($menus as $menu): ?><?php if ($menu['position'] === 'header'): ?><li class="nav-item"><a class="nav-link" href="<?= e((string) $menu['url']) ?>"><?= e((string) $menu['title']) ?></a></li><?php endif; ?><?php endforeach; ?></ul><div class="d-flex gap-2 flex-wrap"><?php if ($user): ?><span class="small text-muted align-self-center">Hi, <?= e((string) $user['username']) ?></span><?php if ($user['role'] === 'super_admin'): ?><a class="btn btn-outline-primary btn-sm" href="admin/index.php">Admin</a><?php endif; ?><a class="btn btn-primary btn-sm" href="submit.php">Submit News</a><a class="btn btn-outline-secondary btn-sm" href="logout.php">Logout</a><?php else: ?><a class="btn btn-outline-primary btn-sm" href="login.php">Login</a><a class="btn btn-primary btn-sm" href="register.php">Register</a><?php endif; ?></div></div></div></nav>
<main class="container py-5"><?php if ($flash): ?><div class="alert alert-<?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?><section class="mb-4"><h1 class="display-5 fw-bold"><?= e($siteName) ?></h1><p class="lead text-muted">A secure public news platform for trusted community publishing.</p></section><div class="d-flex flex-wrap gap-2 mb-4"><a class="btn btn-sm <?= $category === '' ? 'btn-primary' : 'btn-outline-primary' ?>" href="index.php">All</a><?php foreach ($categories as $cat): ?><a class="btn btn-sm <?= $category === $cat['slug'] ? 'btn-primary' : 'btn-outline-primary' ?>" href="index.php?category=<?= urlencode((string) $cat['slug']) ?>"><?= e((string) $cat['name']) ?></a><?php endforeach; ?></div><section class="row g-4"><?php if (!$news): ?><div class="col-12"><div class="alert alert-info">No published news found.</div></div><?php endif; ?><?php foreach ($news as $item): ?><div class="col-md-6 col-lg-4"><article class="card h-100 shadow-sm border-0 news-card"><div class="card-body"><span class="badge text-bg-primary mb-2"><?= e((string) ($item['category_name'] ?? 'General')) ?></span><h5 class="card-title"><a class="text-decoration-none" href="news.php?id=<?= (int) $item['id'] ?>"><?= e((string) $item['title']) ?></a></h5><p class="card-text text-muted"><?= e(substr(strip_tags((string) $item['content']), 0, 140)) ?>...</p></div><div class="card-footer bg-white border-0"><small class="text-muted">By <?= (int) $item['is_anonymous'] === 1 ? 'Anonymous' : e((string) ($item['author_username'] ?? 'Member')) ?></small></div></article></div><?php endforeach; ?></section></main>
<footer class="bg-dark text-white"><div class="container py-4 d-flex justify-content-between flex-wrap gap-2"><span><?= e($footerCopyright) ?></span><span><?php foreach ($menus as $menu): ?><?php if ($menu['position'] === 'footer'): ?><a class="text-white-50 me-3" href="<?= e((string) $menu['url']) ?>"><?= e((string) $menu['title']) ?></a><?php endif; ?><?php endforeach; ?></span></div></footer><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
