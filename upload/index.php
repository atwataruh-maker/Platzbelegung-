<?php
declare(strict_types=1);
use Vfl\{App,Auth,Helpers,Matches,Planning,Cabins};
try{
 require __DIR__.'/server/bootstrap.php';$nonce=App::headers();
 $path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);$base=App::base();
 if($base&&($path!==$base&&!str_starts_with($path,$base.'/'))){http_response_code(404);exit;}
 $route='/'.trim(substr($path,strlen($base)),'/');if($route==='/index.php')$route='/';
 $method=$_SERVER['REQUEST_METHOD'];
 if($route==='/api/auth')Auth::handle($method);
 if($route==='/api/helfer')Helpers::handle($method);
 if($method!=='GET'&&$method!=='HEAD')App::json(['error'=>'Methode nicht erlaubt.'],405);
 if(in_array($route,['/api/spiele','/api/belegung','/api/heute'],true)){
  $selected=$_GET['date']??null;if($selected!==null&&(!is_string($selected)||!Planning::validDate($selected)))App::json(['error'=>'Bitte ein gültiges Datum auswählen.'],400);
  $from=$selected??date('Y-m-d');$to=$selected||$route!=='/api/spiele'?$from:(new DateTimeImmutable($from))->modify('+6 days')->format('Y-m-d');
  // Do not serialize public match refreshes behind the visitor's session lock.
  session_write_close();$schedule=null;
  try{$schedule=Matches::load($from,$to);}catch(Throwable $e){error_log('VfL match source: '.$e->getMessage());if($route==='/api/spiele')App::json(['error'=>'Spiele derzeit nicht erreichbar. Bitte erneut versuchen oder FUSSBALL.DE öffnen.'],502);}
  if($route==='/api/spiele')App::json($schedule);
  $a=Planning::allocate($from,$schedule);$a['cabins']=Cabins::allocate($a,$schedule);$a['source']=['state'=>'verified','origin'=>'upload','checkedAt'=>'2026-10-16T05:19:08.343157+00:00'];$a['season']='Aktuelle Saison · Sommerplan';$a['matchesRetrievedAt']=$schedule['retrievedAt']??null;if($route==='/api/heute')App::json(['date'=>$from,'schedule'=>$schedule,'allocation'=>$a]);App::json($a);
 }
 if($route==='/api/plan'){header('Location: '.$base.'/belegungsplan-sommer-2026.pdf',true,302);exit;}
 if(!in_array($route,['/','/platzbelegung','/kabinenbelegung','/ehrenamt','/admin'],true)){http_response_code(404);echo 'Seite nicht gefunden.';exit;}
 $html=file_get_contents(__DIR__.'/frontend-dist/index.html');
 if($html===false)throw new RuntimeException('Frontend-Build fehlt. Bitte das vollständige Upload-Paket verwenden.');
 $public=['base'=>$base,'csrf'=>$_SESSION['csrf'],'legalUrl'=>App::config('legal_url'),'privacyUrl'=>App::config('privacy_url')];
 $script='<script nonce="'.htmlspecialchars($nonce,ENT_QUOTES).'">window.VFL='.json_encode($public,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR).';</script>';
 $html=str_replace('"./assets/','"'.$base.'/assets/',$html);$html=str_replace('href="/favicon.svg"','href="'.$base.'/favicon.svg"',$html);$html=str_replace('</head>',$script.'</head>',$html);
 header('Content-Type: text/html; charset=utf-8');if($method!=='HEAD')echo $html;
}catch(Throwable $e){error_log('VfL request failed: '.$e->getMessage());http_response_code(503);header('Cache-Control: no-store');header('Content-Type: application/json; charset=utf-8');echo json_encode(['error'=>'Die App ist derzeit nicht verfügbar. Bitte Installation und Serverprotokoll prüfen.'],JSON_UNESCAPED_UNICODE);}
