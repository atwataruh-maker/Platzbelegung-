<?php
declare(strict_types=1);
namespace Vfl;
/** Breitensport: maintained weekly offers (server/breitensport.json) plus live course data from the club website. */
final class Breitensport {
 public const ORIGIN='https://www.vfl-ramsdorf.de';
 public const LIST=self::ORIGIN.'/breitensport/kurse';
 private const MAX_COURSES=12;
 public static function offers():array{
  $json=file_get_contents(__DIR__.'/breitensport.json');if($json===false)throw new \RuntimeException('Breitensport-Daten fehlen.');
  $data=json_decode($json,true,32,JSON_THROW_ON_ERROR);if(!is_array($data['offers']??null))throw new \RuntimeException('Breitensport-Daten ungültig.');
  return $data;
 }
 private static function dom(string $html):\DOMXPath{
  $doc=new \DOMDocument();$previous=libxml_use_internal_errors(true);
  try{$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);}finally{libxml_clear_errors();libxml_use_internal_errors($previous);}
  return new \DOMXPath($doc);
 }
 private static function text(?\DOMNode $node):string{return $node?trim(preg_replace('/\s+/u',' ',str_replace("\u{a0}",' ',$node->textContent))??''):'';}
 private static function lines(?\DOMNode $node):array{
  if(!$node)return [];$out=[];
  foreach(preg_split('/\R/u',str_replace("\u{a0}",' ',$node->textContent))?:[] as $line){$line=trim(preg_replace('/\s+/u',' ',$line)??'');if($line!=='')$out[]=$line;}
  return $out;
 }
 /** Only plain image paths below /img/ on the club website are passed on. */
 private static function image(string $src):?string{
  return preg_match('~^/img/[A-Za-z0-9_\-/]+\.(?:png|jpe?g|webp)$~iD',$src)&&!str_contains($src,'..')?self::ORIGIN.$src:null;
 }
 public static function parseList(string $html):array{
  $x=self::dom($html);$courses=[];
  foreach($x->query("//div[contains(concat(' ',normalize-space(@class),' '),' kurs-karte ')]") as $card){
   $link=$x->query(".//h3//a[@href]",$card)->item(0);if(!$link instanceof \DOMElement)continue;
   if(!preg_match('~^/breitensport/kurs/(\d{1,6})$~D',$link->getAttribute('href'),$m))continue;
   $img=$x->query(".//img[@src]",$card)->item(0);$teaser=$x->query(".//div[contains(@class,'kurs-karte-text')]/p",$card)->item(0);
   $courses[(int)$m[1]]=['id'=>(int)$m[1],'title'=>self::text($link),'url'=>self::ORIGIN.'/breitensport/kurs/'.$m[1],'image'=>$img instanceof \DOMElement?self::image($img->getAttribute('src')):null,'teaser'=>self::lines($teaser)];
   if(count($courses)>=self::MAX_COURSES)break;
  }
  return array_values($courses);
 }
 private static function date(string $value):?string{
  if(!preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{2}|\d{4})(?!\d)/u',$value,$m))return null;
  $year=strlen($m[3])===2?2000+(int)$m[3]:(int)$m[3];
  return checkdate((int)$m[2],(int)$m[1],$year)?sprintf('%04d-%02d-%02d',$year,$m[2],$m[1]):null;
 }
 /** Last complete date in the teaser, e.g. "5.11.26-17.12.26" or "27.10.26- 22.12.26". */
 public static function endDate(array $teaser):?string{
  $text=implode(' ',$teaser);if(!preg_match_all('/\d{1,2}\.\d{1,2}\.(?:\d{4}|\d{2})(?!\d)/u',$text,$all)||count($all[0])<1)return null;
  return self::date(end($all[0]));
 }
 private static function number(string $value):?float{return preg_match('/(\d+(?:[.,]\d{1,2})?)/u',$value,$m)?(float)str_replace(',','.',$m[1]):null;}
 public static function parseCourse(string $html,int $id):array{
  $x=self::dom($html);$info=[];
  foreach($x->query("//div[contains(@class,'kurs-info-zeile')]") as $row){
   $label=$x->query(".//div[contains(@class,'kurs-info-label')]",$row)->item(0);if(!$label)continue;
   $key=self::text($label);$holder=$label->parentNode;$value=trim(preg_replace('/\s+/u',' ',str_replace([$label->textContent,"\u{a0}"],['',' '],$holder->textContent))??'');
   $info[$key]=$value;
  }
  if(!$info)throw new \RuntimeException('Kursseite '.$id.' ohne Kursdaten.');
  $price=$info['Preis']??'';
  $member=preg_match('/(?<!Nicht-)Mitglieder:\s*([\d.,]+)/u',$price,$m)?self::number($m[1]):null;
  $guest=preg_match('/Nicht-Mitglieder:\s*([\d.,]+)/u',$price,$m)?self::number($m[1]):null;
  $seats=isset($info['Verfügbare Plätze'])&&preg_match('/^\d+$/',$info['Verfügbare Plätze'])?(int)$info['Verfügbare Plätze']:null;
  $units=isset($info['Einheiten'])&&preg_match('/^\d+$/',$info['Einheiten'])?(int)$info['Einheiten']:null;
  $booking=$x->query("//a[@href='/breitensport/kurs/".$id."/buchen']")->length>0?self::ORIGIN.'/breitensport/kurs/'.$id.'/buchen':null;
  $teaser=self::lines($x->query("//div[contains(@class,'kurs-text')]")->item(0));
  return ['title'=>self::text($x->query('//h1')->item(0)),'leader'=>$info['Kursleiter']??null,'startDate'=>isset($info['Startdatum'])?self::date($info['Startdatum']):null,
   'units'=>$units,'venue'=>isset($info['Ort'])?preg_replace('/\s*-\s*/u',' · ',$info['Ort']):null,'priceMember'=>$member,'priceGuest'=>$guest,'seatsLeft'=>$seats,'bookingUrl'=>$booking,'teaser'=>$teaser];
 }
 public static function merge(array $card,?array $detail):array{
  $teaser=$detail['teaser']??[]?:$card['teaser'];
  $course=array_replace(['leader'=>null,'startDate'=>null,'units'=>null,'venue'=>null,'priceMember'=>null,'priceGuest'=>null,'seatsLeft'=>null,'bookingUrl'=>null],$card,array_filter($detail??[],fn($v)=>$v!==null&&$v!==''&&$v!==[]));
  $course['title']=$card['title']?:($detail['title']??'');$course['teaser']=array_slice($teaser,0,4);
  $course['ongoing']=(bool)preg_grep('/fortlaufend/iu',$teaser);
  $course['endDate']=$course['ongoing']?null:self::endDate($teaser);
  $course['detailsMissing']=$detail===null;
  return $course;
 }
 public static function fetch():array{
  $courses=[];
  foreach(self::parseList(Http::get(self::LIST,2000000,12)) as $card){
   $detail=null;try{$detail=self::parseCourse(Http::get($card['url'],2000000,8),$card['id']);}catch(\Throwable $e){error_log('VfL Breitensport course '.$card['id'].': '.$e->getMessage());}
   $courses[]=self::merge($card,$detail);
  }
  return ['courses'=>$courses,'source'=>self::LIST,'retrievedAt'=>gmdate('c'),'stale'=>false];
 }
 public static function courses():array{return Cache::remember('breitensport-kurse',1800,604800,fn()=>self::fetch());}
 public static function payload():array{
  $data=self::offers();
  try{$data['courses']=self::courses();}catch(\Throwable $e){error_log('VfL Breitensport courses: '.$e->getMessage());$data['courses']=null;}
  return $data;
 }
}
