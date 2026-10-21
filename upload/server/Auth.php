<?php
declare(strict_types=1);
namespace Vfl;
final class Auth {
 public static function handle(string $method):never{
  if($method==='GET')App::json(['canManage'=>(bool)App::admin(),'username'=>App::admin(),'csrf'=>$_SESSION['csrf']]);
  App::mutation();if($method==='DELETE'){$_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));App::json(['canManage'=>false,'csrf'=>$_SESSION['csrf']]);}
  if($method!=='POST')App::json(['error'=>'Methode nicht erlaubt.'],405);$body=App::body();$username=$body['username']??'';$password=$body['password']??'';
  if(!is_string($username)||!is_string($password)||strlen($username)>80||strlen($password)>1024)App::json(['error'=>'Ungültige Anmeldedaten.'],400);
  $username=strtolower(trim($username));$now=time();DB::query('DELETE FROM vfl_login_limits WHERE expires < ?',[$now]);
  foreach([['ip:'.($_SERVER['REMOTE_ADDR']??'unknown'),10],['user:'.$username,30]] as [$key,$limit]){
   $bucket=hash('sha256',$key);DB::query('INSERT INTO vfl_login_limits (bucket,attempts,expires) VALUES (?,1,?) ON DUPLICATE KEY UPDATE attempts=attempts+1',[$bucket,$now+900]);
   $count=DB::query('SELECT attempts FROM vfl_login_limits WHERE bucket=?',[$bucket])->fetchColumn();if($count>$limit){header('Retry-After: 900');App::json(['error'=>'Zu viele Anmeldeversuche. Bitte in 15 Minuten erneut versuchen.'],429);}
  }
  $user=DB::query('SELECT * FROM vfl_admins WHERE username=?',[$username])->fetch();
  $valid=password_verify($password,$user['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
  if(!$user||!$valid)App::json(['error'=>'Benutzername oder Passwort ist falsch.'],401);
  if(password_needs_rehash($user['password_hash'],PASSWORD_DEFAULT))DB::query('UPDATE vfl_admins SET password_hash=? WHERE username=?',[password_hash($password,PASSWORD_DEFAULT),$username]);
  session_regenerate_id(true);$_SESSION['admin']=$username;$_SESSION['last_seen']=time();$_SESSION['csrf']=bin2hex(random_bytes(32));App::json(['canManage'=>true,'username'=>$username,'csrf'=>$_SESSION['csrf']]);
 }
}
