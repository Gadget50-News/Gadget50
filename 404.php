<?php

declare(strict_types=1);

$siteName = 'Gadget 50';
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    if (defined('APP_NAME')) $siteName = (string) APP_NAME;
}
http_response_code(404);
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>404 | <?= htmlspecialchars($siteName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="/assets/css/style.css"></head><body><header class="site-header"><div class="container py-3"><a class="brand" href="/"><span><?= htmlspecialchars($siteName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span></a></div></header><main class="container py-5 text-center"><div class="card border-0 shadow-sm"><div class="card-body p-5"><h1 style="font-size:4rem;">404</h1><h2>Page Not Found</h2><p class="text-muted">The requested page could not be found.</p><a href="/" class="btn btn-primary">Back to homepage</a></div></div></main></body></html>
