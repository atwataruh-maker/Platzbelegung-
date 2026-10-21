<?php
declare(strict_types=1);
namespace Vfl;
final class Helpers {
 const CATEGORIES=['Handwerk','Organisation','Sport & Betreuung','Garten & Gelände','Sonstiges'];
 private static function record(array $r):array{unset($r['created_by']);$r['hours']=$r['hours']===null?null:(float)$r['hours'];$r['is_example']=(int)$r['is_example'];return $r;}
 private static function text(array $body,string $key,int $min,int $max):string{if(!isset($body[$key])||!is_string($body[$key]))App::json(['error'=>'Bitte alle Pflichtfelder ausfüllen.'],400);$s=trim($body[$key]);$len=mb_strlen($s);if($len<$min||$len>$max)App::json(['error'=>'Ungültige Länge für '.$key.'.'],400);return $s;}
 public static function handle(string $method):never{
  if($method==='GET'){$rows=DB::query('SELECT * FROM vfl_helper_requests ORDER BY status DESC, created_at DESC, id')->fetchAll();App::json(['records'=>array_map([self::class,'record'],$rows),'canManage'=>(bool)App::admin()]);}
  if(!App::admin())App::json(['error'=>'Bitte als Admin anmelden, um Gesuche zu verwalten.'],403);App::mutation();$body=App::body();
  if($method==='POST'){
   if(array_diff(array_keys($body),['id','title','description','category','hours','location','timeframe','contact']))App::json(['error'=>'Unbekannte Eingabefelder.'],400);
   $id=self::text($body,'id',36,36);if(!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/Di',$id))App::json(['error'=>'Ungültige Kennung.'],400);
   $input=['id'=>$id,'title'=>self::text($body,'title',3,120),'description'=>self::text($body,'description',5,3000),'category'=>self::text($body,'category',1,40),'hours'=>$body['hours']??null,'location'=>self::text($body,'location',0,160),'timeframe'=>self::text($body,'timeframe',1,160),'contact'=>self::text($body,'contact',0,200)];
   if(!in_array($input['category'],self::CATEGORIES,true)||($input['hours']!==null&&(!is_int($input['hours'])&&!is_float($input['hours'])||$input['hours']<0.25||$input['hours']>10000)))App::json(['error'=>'Bitte Bereich und Zeitaufwand prüfen.'],400);
   try{DB::query("INSERT INTO vfl_helper_requests (id,title,description,category,hours,location,timeframe,contact,status,is_example,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,'open',0,?,?)",[...array_values($input),App::admin(),gmdate('c')]);}
   catch(\PDOException $e){if($e->getCode()!=='23000')throw $e;$old=DB::query('SELECT * FROM vfl_helper_requests WHERE id=?',[$id])->fetch();$same=$old&&$old['created_by']===App::admin();foreach($input as $k=>$v){$ov=$k==='hours'&&$old[$k]!==null?(float)$old[$k]:$old[$k];if($k==='hours'&&$v!==null)$v=(float)$v;if($ov!==$v)$same=false;}if(!$same)App::json(['error'=>'Das Gesuch wurde zwischenzeitlich geändert. Bitte aktualisieren.'],409);}
   App::json(['record'=>self::record(DB::query('SELECT * FROM vfl_helper_requests WHERE id=?',[$id])->fetch())],201);
  }
  if($method==='PATCH'){
   if(array_diff(array_keys($body),['id','expectedStatus','status']))App::json(['error'=>'Unbekannte Eingabefelder.'],400);
   $id=self::text($body,'id',1,80);$expected=$body['expectedStatus']??null;$next=$body['status']??null;
   if(!in_array($expected,['open','completed'],true)||!in_array($next,['open','completed'],true)||$expected===$next)App::json(['error'=>'Ungültiger Status.'],400);
   $q=DB::query('UPDATE vfl_helper_requests SET status=?,completed_at=? WHERE id=? AND status=?',[$next,$next==='completed'?gmdate('c'):null,$id,$expected]);$row=DB::query('SELECT * FROM vfl_helper_requests WHERE id=?',[$id])->fetch();if(!$row)App::json(['error'=>'Das Gesuch wurde nicht gefunden.'],404);
   if(!$q->rowCount()&&$row['status']!==$next)App::json(['error'=>'Das Gesuch wurde zwischenzeitlich geändert. Bitte aktualisieren.'],409);App::json(['record'=>self::record($row)]);
  }
  App::json(['error'=>'Methode nicht erlaubt.'],405);
 }
}
