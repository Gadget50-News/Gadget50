<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email.php';

if (isLoggedIn()) redirect('index.php');
$pdo = Database::getInstance();
$maxAttempts = 5;
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$device = (string) ($_COOKIE['gadget50_device'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $device)) {
    $device = bin2hex(random_bytes(32));
    setcookie('gadget50_device', $device, ['expires'=>time()+31536000,'path'=>appBasePath().'/' ,'secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
}
$key = static fn(string $scope,string $value): string => $scope.':'.hash('sha256',$value);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $identity=trim((string)($_POST['identity']??'')); $password=(string)($_POST['password']??'');
    $keys=[$key('ip',(string)($_SERVER['REMOTE_ADDR']??'unknown')),$key('device',$device),$key('identity',strtolower($identity))];
    $marks=implode(',',array_fill(0,count($keys),'?'));
    try {
        $pdo->beginTransaction();
        $s=$pdo->prepare("SELECT blocked_until FROM login_rate_limits WHERE rate_key IN ($marks) FOR UPDATE");$s->execute($keys);
        foreach($s->fetchAll() as $row) if($row['blocked_until']!==null && strtotime((string)$row['blocked_until'])>time()){ $pdo->rollBack();setFlash('danger','অনেকবার ব্যর্থ হওয়ায় লগইন সাময়িকভাবে বন্ধ আছে।');redirect('login.php'); }
        $s=$pdo->prepare('SELECT * FROM users WHERE username=:identity OR email=:email LIMIT 1');$s->execute([':identity'=>$identity,':email'=>$identity]);$user=$s->fetch();
        $valid=$identity!==''&&$password!==''&&$user&&(string)$user['status']==='active'&&(int)($user['email_verified']??1)===1&&password_verify($password,(string)$user['password_hash']);
        if(!$valid){ foreach($keys as $k){$u=$pdo->prepare('INSERT INTO login_rate_limits(rate_key,failed_attempts,last_failed_at) VALUES(?,1,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE failed_attempts=failed_attempts+1,last_failed_at=UTC_TIMESTAMP(),blocked_until=IF(failed_attempts+1>=5,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 48 HOUR),blocked_until)');$u->execute([$k]);} $c=$pdo->prepare("SELECT COALESCE(MAX(failed_attempts),0) FROM login_rate_limits WHERE rate_key IN ($marks)");$c->execute($keys);$n=(int)$c->fetchColumn();$pdo->commit();setFlash('danger',$n>=5?'লগইন ৪৮ ঘণ্টার জন্য ব্লক হয়েছে.':($n>=2?'ভুল তথ্য। আরও '.($maxAttempts-$n).' বার চেষ্টা করা যাবে।':'লগইন তথ্য সঠিক নয়।'));redirect('login.php');}
        $pdo->prepare("DELETE FROM login_rate_limits WHERE rate_key IN ($marks)")->execute($keys);
        if((int)($user['two_factor_enabled']??0)===1){
            if(!emailServiceAvailable()){ $pdo->rollBack();setFlash('danger','ইমেইল সার্ভিস সক্রিয় না থাকায় 2FA লগইন সম্পন্ন করা যাচ্ছে না।');redirect('login.php'); }
            $code=str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);$hash=hash('sha256',$code);
            $pdo->prepare('DELETE FROM auth_challenges WHERE user_id=:id AND purpose=:purpose AND used_at IS NULL')->execute([':id'=>(int)$user['id'],':purpose'=>'login_2fa']);
            $pdo->prepare('INSERT INTO auth_challenges(user_id,purpose,code_hash,expires_at) VALUES(:id,:purpose,:hash,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 10 MINUTE))')->execute([':id'=>(int)$user['id'],':purpose'=>'login_2fa',':hash'=>$hash]);$pdo->commit();
            if(!sendTwoFactorCodeEmail($user,$code)){ $pdo->prepare('DELETE FROM auth_challenges WHERE user_id=:id AND purpose=:purpose AND code_hash=:hash')->execute([':id'=>(int)$user['id'],':purpose'=>'login_2fa',':hash'=>$hash]);setFlash('danger','2FA কোড পাঠানো যায়নি।');redirect('login.php'); }
            $_SESSION['pending_2fa_user_id']=(int)$user['id'];setFlash('success','আপনার ইমেইলে 2FA কোড পাঠানো হয়েছে।');redirect('two-factor.php');
        }
        $pdo->commit();session_regenerate_id(true);$_SESSION['user_id']=(int)$user['id'];$_SESSION['user_role']=(string)$user['role'];setFlash('success','লগইন সফল হয়েছে।');redirect($user['role']==='super_admin'?'admin/index.php':'index.php');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Login error: '.$e->getMessage());setFlash('danger','লগইন সম্পন্ন করা যায়নি।');redirect('login.php');}
}
$flash=getFlash();
?>
<!doctype html><html lang="bn"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>লগইন | Gadget 50</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><main class="container py-5"><div class="row justify-content-center"><div class="col-md-5"><div class="card border-0 shadow-sm"><div class="card-body p-4"><h2>লগইন</h2><?php if($flash):?><div class="alert alert-<?=e((string)$flash['type'])?>"><?=e((string)$flash['message'])?></div><?php endif;?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><div class="mb-3"><label>ইউজারনেম বা ইমেইল</label><input class="form-control" name="identity" required></div><div class="mb-3"><label>পাসওয়ার্ড</label><input class="form-control" type="password" name="password" required></div><button class="btn btn-primary w-100">লগইন</button></form><div class="mt-3 text-center"><a href="<?=e(appUrl('forgot-password.php'))?>">পাসওয়ার্ড ভুলে গেছেন?</a> · <a href="<?=e(appUrl('register.php'))?>">রেজিস্টার</a></div></div></div></div></div></main></body></html>
