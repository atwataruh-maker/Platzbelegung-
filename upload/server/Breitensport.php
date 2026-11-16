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
  try{$live=self::live();}catch(\Throwable $e){error_log('VfL Breitensport times: '.$e->getMessage());$live=null;}
  return self::reconcile($data,$live);
 }

 // ---- Live times from the club website -------------------------------------------------------
 /** Pages whose offers are compared; key = last path segment used in an offer's url. */
 public const PAGES=['breitensport'=>'/breitensport','kindersport'=>'/breitensport/kindersport','seniorensport'=>'/breitensport/seniorensport','mannschaftssport'=>'/breitensport/mannschaftssport',
  'eltern-kind-fussball'=>'/breitensport/eltern-kind-fussball','sportabzeichen'=>'/breitensport/sportabzeichen','kurse'=>'/breitensport/kurse'];
 private const DAYS=['montag'=>1,'dienstag'=>2,'mittwoch'=>3,'donnerstag'=>4,'freitag'=>5,'samstag'=>6,'sonntag'=>0];
 private const LEADER='(?:Übungsleiter(?:in)?|Kursleiter(?:in)?|Trainer(?:in)?|Großmeister(?:in)?)';
 private static function clock(string $h,?string $m):?string{$h=(int)$h;$m=(int)($m??0);return $h<24&&$m<60?sprintf('%02d:%02d',$h,$m):null;}
 /** "Mittwochs 16:00 – 17:00 Uhr", "Samstags: 9:00 - 10:00 Uhr", "jeweils dienstags 17-18 Uhr", "immer donnerstags ab 19.00 Uhr", "15:30 - 16:30 Uhr". */
 public static function parseTime(string $text,?int $day=null):?array{
  $text=str_replace(["\u{a0}",'–','—'],[' ','-','-'],$text);
  if(preg_match('/\b(montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag)s?\b/iu',$text,$d))$day=self::DAYS[mb_strtolower($d[1])];
  if($day===null)return null;
  // A time must not be part of a date such as 5.11.26.
  $t='(\d{1,2})(?:[:.](\d{2}))?(?![.\d])\s*(?:h\b|Uhr)?';
  // Ranges need minutes or "Uhr"/"h", so "(4-7 J.)" is an age, not a time.
  if(preg_match('/(?<![\d.])'.$t.'\s*-\s*'.$t.'/u',$text,$m)&&preg_match('/[:.]|Uhr|h\b/u',$m[0])){$s=self::clock($m[1],$m[2]?:null);$e=self::clock($m[3],($m[4]??'')?:null);if($s&&$e&&$s<$e)return ['day'=>$day,'start'=>$s,'end'=>$e];}
  if(preg_match('/(?<![\d.])(\d{1,2})[:.](\d{2})\s*(?:h\b|Uhr)/u',$text,$m)&&($s=self::clock($m[1],$m[2])))return ['day'=>$day,'start'=>$s,'end'=>null];
  return null;
 }
 /** Text of a node with one line per block element / <br>. */
 private static function blockLines(\DOMNode $node):array{
  $buf='';$walk=function(\DOMNode $n)use(&$walk,&$buf){
   foreach($n->childNodes as $c){
    if($c instanceof \DOMText){$buf.=$c->data;continue;}
    if(!$c instanceof \DOMElement||in_array($c->tagName,['script','style','img'],true))continue;
    $block=in_array($c->tagName,['p','div','h1','h2','h3','h4','h5','h6','li','tr','td','th','br','section','figure'],true);
    if($block)$buf.="\n";$walk($c);if($block)$buf.="\n";
   }
  };$walk($node);
  $out=[];foreach(preg_split('/\R/u',str_replace("\u{a0}",' ',$buf))?:[] as $line){$line=trim(preg_replace('/\s+/u',' ',$line)??'');if($line!=='')$out[]=$line;}
  return $out;
 }
 /**
  * Detail pages: each block lists leader, "Zeit", "Ort" and "Beschreibung", either as headings
  * followed by values or inline ("Zeit: Donnerstags 17:00 – 18:00 Uhr"). One entry per time line.
  */
 public static function parseDetails(string $html,string $page):array{
  $x=self::dom($html);$entries=[];
  foreach($x->query("//div[starts-with(@id,'block-')][not(.//div[contains(concat(' ',normalize-space(@class),' '),' ekf-card ')])] | //div[contains(concat(' ',normalize-space(@class),' '),' ekf-card ')]") as $block){
   $title=self::text($x->query('.//h1|.//h3',$block)->item(0));$lines=self::blockLines($block);
   $group=null;$label=null;$leader=null;
   $flush=function()use(&$group,&$entries,$page,$title){
    if($group)foreach($group['times'] as $t)$entries[]=['page'=>$page,'title'=>$title,'leader'=>$group['leader'],'venue'=>$group['venue'],'audience'=>$group['audience']]+$t;
    $group=null;
   };
   foreach($lines as $line){
    if(preg_match('/^('.self::LEADER.'|Zeit|Trainingszeit|Ort|Beschreibung)(?:\s*:\s*(.*)|\s*)$/iu',$line,$m)){
     $label=mb_strtolower($m[1]);$value=trim($m[2]??'');
     if(preg_match('/^'.self::LEADER.'$/iu',$m[1])){$flush();$label='leader';$group=['leader'=>null,'times'=>[],'venue'=>null,'audience'=>null];if(preg_match('/^großmeister/iu',$m[1])&&$value==='')$leader='Großmeister ';else $leader='';}
     elseif(in_array($label,['zeit','trainingszeit'],true)&&$group&&$group['times']&&($group['venue']||$group['audience'])){$keep=$group['leader'];$flush();$group=['leader'=>$keep,'times'=>[],'venue'=>null,'audience'=>null];}
     $group??=['leader'=>null,'times'=>[],'venue'=>null,'audience'=>null];
     if($value==='')continue;$line=$value;
    }
    if(!$group||$label===null)continue;
    if($label==='leader'&&$group['leader']===null&&!preg_match('/^(?:Tel|E-Mail|\+?\d)/iu',$line)){$group['leader']=trim(($leader??'').rtrim($line,', '));continue;}
    if($label==='zeit'||$label==='trainingszeit'){
     if($t=self::parseTime($line)){$group['times'][]=$t;continue;}
     if($label==='trainingszeit'&&$group['times']&&!$group['venue'])$group['venue']=$line;
     continue;
    }
    if($label==='ort'&&!$group['venue'])$group['venue']=$line;
    elseif($label==='beschreibung'&&!$group['audience'])$group['audience']=$line;
   }
   $flush();
  }
  return $entries;
 }
 /** Overview tables (one per venue, Mo–Sa columns): only used for offers without own detail page. */
 public static function parseGrid(string $html):array{
  $x=self::dom($html);$entries=[];
  foreach($x->query("//div[contains(@class,'angebote-card')]") as $card){
   $venue=self::text($x->query('.//h4',$card)->item(0));$days=[];
   foreach($x->query('.//thead//th',$card) as $th)$days[]=self::DAYS[mb_strtolower(rtrim(self::text($th),'s'))]??null;
   foreach($x->query('.//tbody/tr',$card) as $tr){$i=0;foreach($x->query('./td',$tr) as $td){
    $lines=self::blockLines($td);$day=$days[$i++]??null;if(!$lines||$day===null)continue;
    foreach($lines as $line)if($t=self::parseTime($line,$day)){$entries[]=['page'=>'breitensport','title'=>implode(' ',array_filter($lines,fn($l)=>!self::parseTime($l,$day))),'leader'=>null,'venue'=>$venue,'audience'=>null]+$t;break;}
   }}
  }
  return $entries;
 }
 public static function fetchLive():array{
  $entries=[];$pages=[];
  foreach(self::PAGES as $key=>$path){
   try{$html=Http::get(self::ORIGIN.$path,2000000,10);$found=$key==='breitensport'?self::parseGrid($html):self::parseDetails($html,$key);
    // An empty result means a changed layout: treat the page as unchecked instead of reporting every offer as missing.
    if($found){$entries=array_merge($entries,$found);$pages[$key]=true;}else{$pages[$key]=false;error_log('VfL Breitensport: no times found on '.$path);}}
   catch(\Throwable $e){$pages[$key]=false;error_log('VfL Breitensport page '.$path.': '.$e->getMessage());}
  }
  if(!array_filter($pages))throw new \RuntimeException('Keine Breitensport-Seite erreichbar.');
  return ['entries'=>$entries,'pages'=>$pages,'retrievedAt'=>gmdate('c'),'stale'=>false];
 }
 public static function live():array{return Cache::remember('breitensport-zeiten',3600,604800,fn()=>self::fetchLive());}

 private static function key(string $value):string{return trim(preg_replace('/[^a-z0-9]+/','',strtolower(strtr(\Normalizer::normalize($value,\Normalizer::FORM_D)?:$value,['ß'=>'ss']))));}
 private static function minutes(string $t):int{[$h,$m]=array_map('intval',explode(':',$t));return $h*60+$m;}
 private static function sameVenue(?string $a,?string $b):bool{
  if(!$a||!$b)return true;$a=self::key(str_replace(['Sporthalle','Sportplatz'],'',$a));$b=self::key(str_replace(['Sporthalle','Sportplatz'],'',$b));
  return $a===$b||str_contains($a,$b)||str_contains($b,$a);
 }
 private static function pageOf(string $url):string{$path=parse_url($url,PHP_URL_PATH)?:'';$seg=basename(rtrim($path,'/'));return isset(self::PAGES[$seg])?$seg:'breitensport';}
 /**
  * Compare maintained sessions with the website. The website wins: changed times (and venues that are
  * clearly different) replace the maintained values, the previous ones are kept in `planned`.
  * Course sessions use the time from the course text ("jeweils donnerstags, 16.30h").
  */
 public static function reconcile(array $data,?array $live):array{
  $entries=$live['entries']??[];$pages=$live['pages']??[];$used=[];
  foreach($data['offers'] as &$offer){
   $page=self::pageOf($offer['url']);
   foreach($offer['sessions'] as &$s){
    $found=null;$source=null;
    if(isset($s['course'])&&is_array($data['courses']??null)){
     foreach($data['courses']['courses'] as $c)if(self::key($c['title'])===self::key($s['course'])){
      foreach($c['teaser']??[] as $line)if(($t=self::parseTime($line))&&$t['day']===$s['day']){$found=$t;$source='kurs';break 2;}
      break;
     }
    }
    if(!$found&&!empty($pages[$page])){
     $best=null;$score=PHP_INT_MAX;
     // First by leader, then (e.g. after a change of leader) by the offer's name.
     foreach([true,false] as $byLeader){
      foreach($entries as $i=>$e){
       if($e['page']!==$page||$e['day']!==$s['day'])continue;
       $titleMatch=$e['title']!==''&&str_contains(self::key($e['title']),self::key($offer['title']));
       if($byLeader){
        if(isset($s['leader'])&&$e['leader']!==null){if(self::key($e['leader'])!==self::key($s['leader'])&&!str_contains(self::key($e['leader']),self::key($s['leader'])))continue;}
        elseif(!$titleMatch)continue;
       }elseif(!$titleMatch)continue;
       $d=abs(self::minutes($e['start'])-self::minutes($s['start']))+(self::sameVenue($s['venue']??null,$e['venue'])?0:300)+(isset($used[$i])?600:0);
       if($d<$score){$score=$d;$best=$i;}
      }
      if($best!==null)break;
     }
     if($best!==null){$found=$entries[$best];$used[$best]=true;$source='seite';}
    }
    if(!$found){$s['live']=['state'=>!empty($pages[$page])&&!isset($s['course'])?'missing':'unchecked'];continue;}
    $end=$found['end']??null;if($end===null)$end=sprintf('%02d:%02d',intdiv($m=self::minutes($found['start'])+self::minutes($s['end'])-self::minutes($s['start']),60)%24,$m%60);
    $venue=($found['venue']??null)&&!self::sameVenue($s['venue']??null,$found['venue'])?$found['venue']:($s['venue']??null);
    $leader=($found['leader']??null)&&isset($s['leader'])&&self::key($found['leader'])!==self::key($s['leader'])&&!str_contains(self::key($found['leader']),self::key($s['leader']))?$found['leader']:($s['leader']??null);
    $changed=$found['start']!==$s['start']||$end!==$s['end']||$venue!==($s['venue']??null)||$leader!==($s['leader']??null);
    $s['live']=['state'=>$changed?'updated':'confirmed','source'=>$source]+($changed?['planned'=>['start'=>$s['start'],'end'=>$s['end'],'venue'=>$s['venue']??null,'leader'=>$s['leader']??null]]:[]);
    $s['start']=$found['start'];$s['end']=$end;if($venue!==null)$s['venue']=$venue;
    if($leader!==($s['leader']??null)){$s['leader']=$leader;if(!in_array($leader,array_column($offer['leaders'],'name'),true))$offer['leaders'][]=['name'=>$leader];}
   }
   unset($s);
   // Drop leaders the website no longer lists for any session of this offer.
   if(array_filter($offer['sessions'],fn($x)=>isset($x['live']['planned']['leader'])&&$x['live']['planned']['leader']!==$x['leader'])){
    $names=array_column($offer['sessions'],'leader');$offer['leaders']=array_values(array_filter($offer['leaders'],fn($l)=>in_array($l['name'],$names,true)));
   }
  }
  unset($offer);
  // Course blocks on the Kurse page are covered by the course texts above.
  $byCourse=[];foreach($data['offers'] as $o)if($o['sessions']&&!array_filter($o['sessions'],fn($x)=>($x['live']['source']??'')!=='kurs'))$byCourse[self::key($o['title'])]=true;
  $data['live']=$live?['retrievedAt'=>$live['retrievedAt']??null,'stale'=>(bool)($live['stale']??false),'pages'=>$pages,
   'unmatched'=>array_values(array_map(fn($e)=>array_intersect_key($e,array_flip(['page','title','day','start','end','venue'])),array_filter(array_diff_key($entries,$used),fn($e)=>$e['page']!=='breitensport'&&!isset($byCourse[self::key($e['title'])]))))]:null;
  return $data;
 }
}
