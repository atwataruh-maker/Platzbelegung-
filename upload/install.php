<?php
declare(strict_types=1);
use Vfl\{App,DB};
$error='';$done=false;$locked=false;
try{
 require __DIR__.'/server/bootstrap.php';$nonce=App::headers();
 $token=App::config('setup_token');$locked=is_file(App::storage().'/installed.lock')||!is_string($token)||strlen($token)<32;
 if(!$locked){try{$locked=(int)DB::query('SELECT COUNT(*) FROM vfl_admins')->fetchColumn()>0;}catch(PDOException $e){if($e->getCode()!=='42S02')throw $e;}}
 if($locked){http_response_code(403);$error='Installation gesperrt. Bei einer neuen Installation zuerst config.php mit einem eigenen Setup-Schlüssel einrichten.';}
 elseif($_SERVER['REQUEST_METHOD']==='POST'){
  if(($_SERVER['HTTP_ORIGIN']??'')!==App::origin()||!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Ungültige Anfrage. Bitte neu laden.');
  if(!hash_equals($token,(string)($_POST['token']??'')))throw new RuntimeException('Setup-Schlüssel stimmt nicht.');
  $user=strtolower(trim((string)($_POST['username']??'')));$pass=(string)($_POST['password']??'');
  if(!preg_match('/^[a-z0-9._-]{3,80}$/D',$user)||strlen($pass)<12||strlen($pass)>72)throw new RuntimeException('Benutzername: 3–80 Zeichen (a–z, 0–9, . _ -). Passwort: 12–72 Bytes.');
  $lock=fopen(App::storage().'/setup.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('Installation wird bereits ausgeführt.');
  try{
   if(is_file(App::storage().'/installed.lock'))throw new RuntimeException('Bereits installiert.');
   foreach(explode(';',file_get_contents(__DIR__.'/server/schema.sql')) as $sql)if(trim($sql))DB::get()->exec($sql);
   if((int)DB::query('SELECT COUNT(*) FROM vfl_admins')->fetchColumn()>0)throw new RuntimeException('Bereits installiert.');
   DB::query('INSERT INTO vfl_admins (username,password_hash,created_at) VALUES (?,?,?)',[$user,password_hash($pass,PASSWORD_DEFAULT),gmdate('c')]);
   if(file_put_contents(App::storage().'/installed.lock',gmdate('c'))===false)throw new RuntimeException('Admin angelegt. Bitte install.php löschen und den Setup-Schlüssel entfernen.');$done=true;
  }finally{flock($lock,LOCK_UN);fclose($lock);}
 }
}catch(Throwable $e){error_log('VfL setup: '.$e->getMessage());$error=$e instanceof PDOException?'Datenbankverbindung fehlgeschlagen. Bitte config.php und Serverprotokoll prüfen.':$e->getMessage();}
function esc(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>VfL · Installation</title><style>body{font:16px system-ui;background:#f3f5f9;color:#182742;margin:0;padding:24px}main{max-width:500px;margin:4vh auto;background:white;padding:28px;border-radius:20px}label{display:block;margin:18px 0 6px}input,button{box-sizing:border-box;width:100%;padding:12px;border:1px solid #bbc7db;border-radius:8px;font:inherit}button{margin-top:24px;background:#2455df;color:white}p{line-height:1.6}.error{color:#a12626}</style><main><h1>VfL Ramsdorf · Einrichtung</h1>
<?php if($error):?><p class="error" role="alert"><?=esc($error)?></p><?php endif;?>
<?php if($done):?><p>Installation abgeschlossen. Bitte <strong>install.php löschen</strong> und <strong>setup_token in config.php leeren</strong>.</p><a href="<?=esc(App::base())?>/admin">Zur Admin-Anmeldung</a>
<?php elseif(!$locked&&isset($_SESSION['csrf'])):?><p>Der Setup-Schlüssel steht ausschließlich in eurer config.php. Es werden leere Tabellen mit Präfix vfl_ und ein Admin-Konto angelegt.</p><form method="post"><input type="hidden" name="csrf" value="<?=esc($_SESSION['csrf'])?>"><label for="token">Setup-Schlüssel</label><input id="token" name="token" type="password" required autocomplete="off"><label for="username">Admin-Benutzername</label><input id="username" name="username" required pattern="[a-z0-9._-]{3,80}" autocomplete="username"><label for="password">Admin-Passwort (mindestens 12 Zeichen)</label><input id="password" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"><button>Installation abschließen</button></form><?php endif;?></main></html>
