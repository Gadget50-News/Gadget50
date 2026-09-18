<?php
declare(strict_types=1);

function mailDefaults(string $provider): array {
    return match (strtolower($provider)) {
        'gmail' => ['host'=>'smtp.gmail.com','port'=>587,'encryption'=>'tls'],
        'zoho' => ['host'=>'smtp.zoho.com','port'=>587,'encryption'=>'tls'],
        default => ['host'=>'','port'=>587,'encryption'=>'tls'],
    };
}
function getMailConfig(): array {
    $provider=getSetting('email_provider',''); $d=mailDefaults($provider);
    return ['provider'=>$provider,'enabled'=>getSetting('email_service_enabled','0')==='1','validated'=>getSetting('email_smtp_validated','0')==='1','host'=>getSetting('smtp_host',$d['host']),'port'=>(int)getSetting('smtp_port',(string)$d['port']),'encryption'=>getSetting('smtp_encryption',$d['encryption']),'username'=>getSetting('smtp_username',''),'password'=>getSetting('smtp_password',''),'from_email'=>getSetting('smtp_from_email',''),'from_name'=>getSetting('smtp_from_name',defined('APP_NAME')?APP_NAME:'Gadget 50')];
}
function emailServiceAvailable(): bool { $c=getMailConfig(); return $c['enabled'] && $c['validated']; }
function sendSmtpMail(string $to,string $subject,string $body,array $c): bool {
    if (!filter_var($to,FILTER_VALIDATE_EMAIL)||!filter_var((string)$c['from_email'],FILTER_VALIDATE_EMAIL)||$c['host']===''||$c['username']===''||$c['password']==='') return false;
    $enc=strtolower((string)$c['encryption']); $scheme=$enc==='ssl'?'ssl://':'tcp://'; $socket=@stream_socket_client($scheme.$c['host'].':'.(int)$c['port'],$errno,$error,15,STREAM_CLIENT_CONNECT); if(!$socket){error_log('SMTP connection failed: '.(string)$errno);return false;}
    stream_set_timeout($socket,15); $read=function($s){$o='';while(($l=fgets($s,512))!==false){$o.=$l;if(isset($l[3])&&$l[3]===' ')break;}return $o;}; $cmd=function($s,$command)use($read){fwrite($s,$command."\r\n");$r=$read($s);return isset($r[0])&&in_array(substr($r,0,3),['220','221','235','250','251','334','354'],true);};
    if(substr($read($socket),0,3)!=='220'||!$cmd($socket,'EHLO localhost')){fclose($socket);return false;}
    if($enc==='tls'&&(!$cmd($socket,'STARTTLS')||!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)||!$cmd($socket,'EHLO localhost'))){fclose($socket);return false;}
    if(!$cmd($socket,'AUTH LOGIN')||!$cmd($socket,base64_encode((string)$c['username']))||!$cmd($socket,base64_encode((string)$c['password']))||!$cmd($socket,'MAIL FROM:<'.(string)$c['from_email'].'>')||!$cmd($socket,'RCPT TO:<'.$to.'>')||!$cmd($socket,'DATA')){fclose($socket);return false;}
    $safeSubject=preg_replace('/[\r\n]+/',' ', $subject); $headers='From: '.str_replace(["\r","\n"],' ',(string)$c['from_name']).' <'.$c['from_email'].">\r\nTo: <".$to.">\r\nSubject: ".$safeSubject."\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"; fwrite($socket,$headers.chunk_split(base64_encode($body),76,"\r\n")."\r\n.\r\n"); $ok=substr($read($socket),0,3)==='250'; fwrite($socket,"QUIT\r\n");fclose($socket);return $ok;
}
function sendEmail(string $to,string $subject,string $body): bool { if(!emailServiceAvailable())return false; return sendSmtpMail($to,$subject,$body,getMailConfig()); }
function sendUserEmailVerification(array $u): bool { $t=generateSecureToken();$p=Database::getInstance();$p->prepare('UPDATE users SET email_verification_token=:t,email_verification_expires_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 24 HOUR) WHERE id=:id')->execute([':t'=>$t,':id'=>(int)$u['id']]);return sendEmail((string)$u['email'],'Verify your email','<p>Verify your account: <a href="'.e(appUrl('verify-email.php?token='.$t)).'">Verify email</a></p>'); }
function sendPasswordResetEmail(array $u): bool { $t=generateSecureToken();$p=Database::getInstance();$p->prepare('UPDATE users SET password_reset_token=:t,password_reset_expires_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 HOUR) WHERE id=:id')->execute([':t'=>$t,':id'=>(int)$u['id']]);return sendEmail((string)$u['email'],'Password reset','<p>Reset your password: <a href="'.e(appUrl('reset-password.php?token='.$t)).'">Reset password</a></p>'); }
function sendAdminNewUserEmail(array $u): bool { $to=getSetting('admin_alert_email','');return $to===''?true:sendEmail($to,'New user registration','<p>New user: '.e((string)$u['username']).'</p>'); }
function sendTwoFactorCodeEmail(array $u,string $code): bool { return sendEmail((string)$u['email'],'Your two-factor login code','<p>Your verification code is: <strong>'.e($code).'</strong></p>'); }
