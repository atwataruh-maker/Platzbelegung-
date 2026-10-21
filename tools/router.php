<?php
// PHP development router; production uses Apache .htaccess.
$docroot=getenv('VFL_DOCROOT')?:dirname(__DIR__);
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$config=require $docroot.'/config.php';$base=rtrim(parse_url($config['app_url'],PHP_URL_PATH)??'','/');$path=substr($path,strlen($base));
if(preg_match('~(?:^|/)\.|^/(?:server|var|tools|tests|frontend|frontend-dist|app|lib|components|config\.php)(?:/|$)~',$path)){http_response_code(403);return true;}
$file=realpath($docroot.$path);$root=realpath($docroot);
if($file&&str_starts_with($file,$root.'/')&&is_file($file)&&$path!=='/index.php'&&$path!=='/install.php'){
 if(!preg_match('/\.(?:js|css|png|svg|pdf|webmanifest)$/',$file)){http_response_code(403);return true;}
 $types=['js'=>'text/javascript','css'=>'text/css','svg'=>'image/svg+xml','png'=>'image/png','pdf'=>'application/pdf','webmanifest'=>'application/manifest+json'];header('Content-Type: '.$types[pathinfo($file,PATHINFO_EXTENSION)]);readfile($file);return true;
}
require $path==='/install.php'?$docroot.'/install.php':$docroot.'/index.php';
