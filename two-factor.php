<?php
declare(strict_types=1);
require_once __DIR__.'/includes/bootstrap.php';
if (isLoggedIn()) redirect('index.php');
$pending=(int)($_SESSION['pending_2fa_user_id']??0); if($pending<=0) {setFlash('danger','ভেরিফিকেশন সেশন শেষ হয়েছে।');redirect('login.php');}
$pdo=Database::getInstance();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verifyCsrf();$code=trim((string)($_POST['two_factor_code']??''));
 if(!preg_match('/^\d{6}$/',$code)){setFlash('danger','৬ সংখ্যার কোড দিন।');redirect('two-factor.php');}
 $s=$pdo->prepare('SELECT * FROM auth_challenges WHERE user_id=:id AND purpose=:purpose AND used_at IS NULL AND expires_at>UTC_TIMESTAMP() ORDER BY id DESC LIMIT 1');$s->execute([':id'=>$pending,':purpose'=>'login_2fa']);$challenge=$s->fetch();
 if(!$challenge||((int)$challenge['attempts']>=5)||!hash_equals((string)$challenge['code_hash'],hash('sha256',$code))){if($challenge)$pdo->prepare('UPDATE auth_challenges SET attempts=attempts+1 WHERE id=:id')->execute([':id'=>(int)$challenge['id']]);setFlash('danger','কোডটি সঠিক নয় বা মেয়াদ শেষ হয়েছে।');redirect('two-factor.php');}
 $pdo->prepare('UPDATE auth_challenges SET used_at=UTC_TIMESTAMP() WHERE id=:id')->execute([':id'=>(int)$challenge['id']]);$s=$pdo->prepare('SELECT id,role FROM users WHERE id=:id AND status="active" AND email_verified=1 LIMIT 1');$s->execute([':id'=>$pending]);$u=$s->fetch();unset($_SESSION['pending_2fa_user_id']);if(!$u){setFlash('danger','অ্যাকাউন্টটি সক্রিয় নয়।');redirect('login.php');}session_regenerate_id(true);$_SESSION['user_id']=(int)$u['id'];$_SESSION['user_role']=(string)$u['role'];setFlash('success','2FA যাচাই সফল হয়েছে।');redirect($u['role']==='super_admin'?'admin/index.php':'index.php');
}
$flash=getFlash();?><!doctype html><html lang="bn"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>2FA যাচাই</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><main class="container py-5"><div class="row justify-content-center"><div class="col-md-5"><div class="card"><div class="card-body"><h2>2FA যাচাই</h2><p>আপনার ইমেইলে পাঠানো ৬ সংখ্যার কোড দিন।</p><?php if($flash):?><div class="alert alert-<?=e((string)$flash['type'])?>"><?=e((string)$flash['message'])?></div><?php endif;?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input class="form-control mb-3" name="two_factor_code" inputmode="numeric" maxlength="6" required><button class="btn btn-primary w-100">যাচাই করুন</button></form></div></div></div></div></main></body></html>
