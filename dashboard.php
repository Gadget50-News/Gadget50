<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin('login.php');
$user = currentUser();
if (!$user) redirect('login.php');
$pdo = Database::getInstance();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $enable = (string) ($_POST['enabled'] ?? '') === '1';
    if ($enable && !function_exists('emailServiceAvailable')) { setFlash('danger', 'Email service is unavailable.'); }
    elseif ($enable && !emailServiceAvailable()) { setFlash('danger', 'Two-factor authentication is currently unavailable because the website email service has not been activated.'); }
    elseif ($enable && (int) ($user['email_verified'] ?? 0) !== 1) { setFlash('danger', 'Verify your email address before enabling two-factor authentication.'); }
    else { $pdo->prepare('UPDATE users SET two_factor_enabled = :enabled WHERE id = :id')->execute([':enabled'=>$enable ? 1 : 0, ':id'=>(int)$user['id']]); setFlash('success', $enable ? 'Two-factor authentication enabled.' : 'Two-factor authentication disabled.'); }
    redirect('dashboard.php');
}
$mine=$pdo->prepare('SELECT n.id,n.title,n.status,n.is_anonymous,n.image,n.created_at,c.name AS category_name FROM news n LEFT JOIN categories c ON c.id=n.category_id WHERE n.author_id=:author ORDER BY n.created_at DESC');$mine->execute([':author'=>(int)$user['id']]);$posts=$mine->fetchAll();$siteName=getSetting('site_name',APP_NAME);$flash=getFlash();$emailAvailable=emailServiceAvailable();
?><!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My newsroom | <?=e($siteName)?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="<?=e(appUrl('assets/css/style.css'))?>"></head><body class="dashboard-body"><main class="container py-5"><div class="d-flex justify-content-between align-items-center mb-4"><div><h1>My newsroom</h1><p>Welcome back, <?=e((string)$user['username'])?>.</p></div><a class="btn btn-outline-secondary" href="<?=e(appUrl('index.php'))?>">Public site</a></div><?php if($flash):?><div class="alert alert-<?=e((string)$flash['type'])?>"><?=e((string)$flash['message'])?></div><?php endif;?><div class="card border-0 shadow-sm mb-4"><div class="card-body"><h2>Account security</h2><p><?=$emailAvailable?'Email service is active.':'Two-factor authentication is unavailable because Email Service is OFF.'?></p><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="enabled" value="<?=((int)($user['two_factor_enabled']??0)===1)?'0':'1'?>"><button class="btn btn-primary" <?=$emailAvailable||((int)($user['two_factor_enabled']??0)===1)?'':'disabled'?>>Two-factor authentication: <?=((int)($user['two_factor_enabled']??0)===1)?'ON — Disable':'OFF — Enable'?></button></form></div></div><div class="card border-0 shadow-sm"><div class="card-body"><h2>Your stories</h2><div class="table-responsive"><table class="table"><thead><tr><th>Story</th><th>Category</th><th>Status</th><th>Date</th></tr></thead><tbody><?php foreach($posts as $post):?><tr><td><a href="<?=e(appUrl('news.php?id='.(int)$post['id']))?>"><?=e((string)$post['title'])?></a></td><td><?=e((string)($post['category_name']??'General'))?></td><td><?=e(ucfirst((string)$post['status']))?></td><td><?=e(date('M d, Y',strtotime((string)$post['created_at'])))?></td></tr><?php endforeach;?></tbody></table></div></div></div></main></body></html>
