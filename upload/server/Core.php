<?php
declare(strict_types=1);
namespace Vfl;
final class App {
 private static array $config=[];
 public static function boot():void{
  $file=dirname(__DIR__).'/config.php';if(!is_file($file))throw new \RuntimeException('Bitte config.php gemäß INSTALLATION.md einrichten.');
  self::$config=require $file;
  $url=rtrim(self::config('app_url'),'/');$parts=parse_url($url);
  if(!$parts||!isset($parts['host'])||!in_array($parts['scheme']??'', ['https','http'],true)||isset($parts['query'])||isset($parts['fragment']))throw new \RuntimeException('Ungültige app_url.');
  if($parts['scheme']!=='https'&&!in_array($parts['host'],['localhost','127.0.0.1'],true))throw new \RuntimeException('HTTPS ist für den Betrieb erforderlich.');
  date_default_timezone_set('Europe/Berlin');
  $dir=self::storage();foreach(['','/cache','/sessions'] as $sub)if(!is_dir($dir.$sub)&&!mkdir($dir.$sub,0700,true)&&!is_dir($dir.$sub))throw new \RuntimeException('Speicherverzeichnis nicht verfügbar.');
  session_save_path($dir.'/sessions');ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');
  session_name('vfl_'.substr(hash('sha256',$url),0,12));session_set_cookie_params(['lifetime'=>0,'path'=>self::base().'/','secure'=>$parts['scheme']==='https','httponly'=>true,'samesite'=>'Lax']);session_start();
  if(isset($_SESSION['admin'])&&time()-($_SESSION['last_seen']??0)>7200){$_SESSION=[];session_regenerate_id(true);}
  if(isset($_SESSION['admin']))$_SESSION['last_seen']=time();
  $_SESSION['csrf']??=bin2hex(random_bytes(32));
 }
 public static function config(string $key,mixed $default=''):mixed{return self::$config[$key]??$default;}
 public static function base():string{return rtrim(parse_url(self::config('app_url'),PHP_URL_PATH)??'','/');}
 public static function origin():string{$p=parse_url(self::config('app_url'));return $p['scheme'].'://'.$p['host'].(isset($p['port'])?':'.$p['port']:'');}
 public static function storage():string{return rtrim(self::config('storage_path',dirname(__DIR__).'/var'),'/');}
 public static function admin():?string{return $_SESSION['admin']??null;}
 public static function json(array $data,int $status=200):never{http_response_code($status);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);exit;}
 public static function body():array{
  if((int)($_SERVER['CONTENT_LENGTH']??0)>16000)self::json(['error'=>'Die Eingaben sind zu lang.'],413);
  $raw=file_get_contents('php://input',false,null,0,16001);if(strlen($raw)>16000)self::json(['error'=>'Die Eingaben sind zu lang.'],413);
  try{$data=json_decode($raw,true,32,JSON_THROW_ON_ERROR);}catch(\Throwable){self::json(['error'=>'Ungültige Eingaben.'],400);}
  if(!is_array($data)||array_is_list($data))self::json(['error'=>'Ungültige Eingaben.'],400);return $data;
 }
 public static function mutation():void{
  if(strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0]))!=='application/json'||($_SERVER['HTTP_ORIGIN']??'')!==self::origin()||!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??''))self::json(['error'=>'Sitzung abgelaufen oder ungültige Anfrage. Bitte die Seite neu laden.'],403);
 }
 public static function headers():string{
  $nonce=base64_encode(random_bytes(18));header('X-Content-Type-Options: nosniff');header('Referrer-Policy: strict-origin-when-cross-origin');header('X-Frame-Options: SAMEORIGIN');
  header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce'; style-src 'self' 'unsafe-inline'; img-src 'self' https://www.vfl-ramsdorf.de https://www.fussball.de data:; connect-src 'self'; font-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'");
  header('Cache-Control: no-store');return $nonce;
 }
}
final class DB {
 private static ?\PDO $pdo=null;
 public static function get():\PDO{
  if(self::$pdo)return self::$pdo;$c=App::config('database',[]);
  self::$pdo=new \PDO('mysql:host='.$c['host'].';port='.($c['port']??3306).';dbname='.$c['name'].';charset=utf8mb4',$c['user'],$c['password'],[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION,\PDO::ATTR_EMULATE_PREPARES=>false,\PDO::ATTR_DEFAULT_FETCH_MODE=>\PDO::FETCH_ASSOC]);return self::$pdo;
 }
 public static function query(string $sql,array $args=[]):\PDOStatement{$stmt=self::get()->prepare($sql);$stmt->execute($args);return $stmt;}
}
final class Http {
 public static function get(string $url,int $limit=2000000,int $timeout=15):string{
  // No user-supplied target URLs, redirects, credentials or arbitrary proxy endpoints.
  $p=parse_url($url);if(($p['scheme']??'')!=='https'||($p['host']??'')!=='www.fussball.de'||isset($p['user'])||isset($p['port']))throw new \RuntimeException('Disallowed source');
  $ch=curl_init($url);$body='';curl_setopt_array($ch,[CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>min(10,$timeout),CURLOPT_TIMEOUT=>$timeout,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_USERAGENT=>'VfL-Ramsdorf-Vereinsapp/0.10',CURLOPT_HTTPHEADER=>['Accept: text/html, application/octet-stream'],CURLOPT_WRITEFUNCTION=>function($ch,$chunk)use(&$body,$limit){if(strlen($body)+strlen($chunk)>$limit)return 0;$body.=$chunk;return strlen($chunk);}]);
  $ok=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);if(!$ok||$status<200||$status>=300)throw new \RuntimeException('Source unavailable: '.$status.($error?' ('.$error.')':''),$status);return $body;
 }
}
final class Cache {
 public static function remember(string $key,int|callable $fresh,int $stale,callable $load,bool $markStale=true):array{
  if(!preg_match('/^[a-zA-Z0-9-]{1,100}$/D',$key))throw new \RuntimeException('Invalid cache key');
  $ttl=fn(array $data):int=>is_int($fresh)?$fresh:$fresh($data);
  $dir=App::storage().'/cache';$path=$dir.'/'.$key.'.json';$old=is_file($path)?json_decode(file_get_contents($path),true):null;$age=time()-($old['time']??0);
  if($old&&$age<$ttl($old['data']))return $old['data'];
  $lock=fopen($path.'.lock','c');if(!$lock)throw new \RuntimeException('Cache unavailable');
  $deadline=microtime(true)+20;
  while(!flock($lock,LOCK_EX|LOCK_NB)){
   // Initial page loads ask for fixtures and allocation together: share the first refresh.
   usleep(100000);clearstatcache(true,$path);$updated=is_file($path)?json_decode(file_get_contents($path),true):null;
   if($updated&&time()-$updated['time']<$ttl($updated['data'])){fclose($lock);return $updated['data'];}
   if(microtime(true)>=$deadline){fclose($lock);if($old&&$age<$stale)return $markStale?array_replace($old['data'],['stale'=>true]):$old['data'];throw new \RuntimeException('Refresh pending');}
  }
  clearstatcache(true,$path);$updated=is_file($path)?json_decode(file_get_contents($path),true):null;
  if($updated&&time()-$updated['time']<$ttl($updated['data'])){flock($lock,LOCK_UN);fclose($lock);return $updated['data'];}
  try{$data=$load($old['data']??null);$tmp=tempnam($dir,'write-');if($tmp===false)throw new \RuntimeException('Cache unavailable');try{if(file_put_contents($tmp,json_encode(['time'=>time(),'data'=>$data],JSON_THROW_ON_ERROR))===false||!rename($tmp,$path))throw new \RuntimeException('Cache write failed');}finally{if(is_file($tmp))unlink($tmp);}return $data;}
  catch(\Throwable $e){if($old&&$age<$stale)return $markStale?array_replace($old['data'],['stale'=>true]):$old['data'];throw $e;}
  finally{flock($lock,LOCK_UN);fclose($lock);}
 }
 public static function prune():void{$files=glob(App::storage().'/cache/*.json')?:[];usort($files,fn($a,$b)=>filemtime($b)<=>filemtime($a));foreach($files as $i=>$file)if($i>=128||filemtime($file)<time()-604800)@unlink($file);}
}
