<?php

declare(strict_types=1);

// This endpoint must remain database-independent so it can render for missing
// routes and for database failures without causing a recursive include.
$siteName = 'Gadget 50';
$configPath = __DIR__ . '/config.php';
if (is_file($configPath)) {
    require_once $configPath;
    if (defined('APP_NAME')) {
        $siteName = (string) APP_NAME;
    }
}

http_response_code(404);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 | <?= htmlspecialchars($siteName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container py-3">
        <a class="brand" href="/"><span><?= htmlspecialchars($siteName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span></a>
    </div>
</header>

<main class="container py-5 text-center">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-5">
            <h1 style="font-size:4rem;">404</h1>
            <h2>Page Not Found</h2>
            <p class="text-muted">Sorry, the page you are looking for could not be found. It may have been moved, deleted, or the URL may be incorrect.</p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="/" class="btn btn-primary">Back to Homepage</a>
                <a href="/search" class="btn btn-outline-secondary">Search News</a>
            </div>
        </div>
    </div>
</main>
</body>
</html>
