<?php
declare(strict_types=1);
namespace Vfl;
final class Matches {
 const CLUB='00ES8GN8QS00004CVV0AG08LVUPGND5I';
 public static function source(string $from,string $to):string{return 'https://www.fussball.de/ajax.club.matchplan/-/datum-bis/'.$to.'/datum-von/'.$from.'/id/'.self::CLUB.'/match-type/-1/max/999/mode/PAGE/show-venues/true';}
 private static function plain(string $s):string{return trim(preg_replace('/\s+/u',' ',preg_replace('/[\x{200b}-\x{200d}\x{feff}]/u','',html_entity_decode(preg_replace('/<[^>]*>/',' ',$s),ENT_QUOTES|ENT_HTML5,'UTF-8'))));}
 private static function capture(string $pattern,string $s,int $n=1):string{return preg_match($pattern,$s,$m)?($m[$n]??''):'';}
 public static function parse(string $html,string $from,string $to,array $fonts=[]):array{
  if(!str_contains($html,'club-matchplan-table')||strlen($html)>2000000||str_contains($html,'ajax.club.matchplan.loadmore'))throw new \RuntimeException('Incomplete or unrecognized schedule');
  $table=self::capture('~<tbody\b[^>]*>([\s\S]*?)</tbody>~i',$html);$provisional=str_contains($html,'hint-pre-publish');
  if(!$table){if(preg_match('~<table\b[^>]*>\s*<thead\b[^>]*>[\s\S]*?</thead>\s*</table>~i',$html))return ['matches'=>[],'provisional'=>$provisional,'omittedRows'=>0];throw new \RuntimeException('Missing match table');}
  $chunks=array_slice(preg_split('~<tr\b[^>]*class="[^"]*\brow-headline\b[^"]*"[^>]*>~i',$table),1);
  if(!$chunks&&!preg_match('/keine\s+(?:spiele|begegnungen)|keine.*gefunden/i',self::plain($table)))throw new \RuntimeException('Missing rows');$omittedRows=0;$matches=[];
  foreach($chunks as $chunk){
   if(preg_match('~<td\b[^>]*class="[^"]*\bcolumn-club\b[^"]*"[^>]*>\s*<span[^>]*>\s*spielfrei\s*</span>~i',$chunk))continue;
   try{
    $headline=self::plain(explode('</tr>',$chunk)[0]);if(!preg_match('/(\d{2})\.(\d{2})\.(\d{4})\s*-\s*(.*?)\s*Uhr\s*\|\s*([^|]+)\|\s*(.*)/u',$headline,$d))throw new \RuntimeException('Missing date');
    preg_match_all('~<td\b[^>]*class="[^"]*\bcolumn-club\b[^"]*"[^>]*>([\s\S]*?)</td>~i',$chunk,$cells);$clubs=[];
    foreach($cells[1] as $cell){$ours=str_contains($cell,self::CLUB);$logo=self::capture('~data-responsive-image="((?:https:)?//www\.fussball\.de/export\.media/-/action/getLogo/format/\d+/id/[A-Z0-9]+)"~',$cell);$clubs[]=['name'=>self::plain(self::capture('~<div\b[^>]*class="club-name"[^>]*>([\s\S]*?)</div>~i',$cell)),'ours'=>$ours,'logo'=>$ours?'https://www.vfl-ramsdorf.de/img/VfLRamsdorf.png':(str_starts_with($logo,'//')?'https:'.$logo:$logo)];}
    $url=self::capture('~href="(https://www\.fussball\.de/(?:spiel|spieltag)/[^"<>]+)"~',$chunk);if(count($clubs)!==2||!$clubs[0]['name']||!$clubs[1]['name']||!$url)throw new \RuntimeException('Invalid row');
    $date="$d[3]-$d[2]-$d[1]";if(!Planning::validDate($date))throw new \RuntimeException('Invalid date');$time=preg_match('/^\d{2}:\d{2}$/D',$d[4])?$d[4]:'Zeit offen';
    $scoreHtml=self::capture('~<td\b[^>]*class="column-score"[^>]*>([\s\S]*?)</td>~i',$chunk);$score=self::plain($scoreHtml);
    $read=function($side)use($scoreHtml,$fonts):?string{if(!preg_match('~<span\b([^>]*class="score-'.$side.'"[^>]*)>([\s\S]*?)</span>~i',$scoreHtml,$span))return null;$font=self::capture('/data-obfuscation="([a-z0-9]+)"/',$span[1]);$raw=self::plain($span[2]);if(!$font)return $raw;return implode('',array_map(fn($c)=>$fonts[$font][$c]??(preg_match('/^[0-9-]$/D',$c)?$c:'?'),mb_str_split($raw)));};
    $homeScore=$read('left');$awayScore=$read('right');$status='';
    if(preg_match('/\bo\.E\./i',$score))$status='Ohne Ergebniswertung';elseif(preg_match('/^(\d{2}\.\d{2}\.\d{4})\s+(\d{2}:\d{2})$/D',$score,$moved))$status="Verlegt: $moved[1] $moved[2]";elseif(preg_match('/Ausfall|Absetzung|Abbruch|verlegt|Nichtantritt(?:\s+(?:HEIM|GAST|BEIDE))?|Spielfrei/i',$score,$st))$status=$st[0];
    $id=basename($url);if(str_contains($url,'/spieltag/'))$id.='-'.$date.'-'.$time.'-'.implode('-',array_column($clubs,'name'));
    $m=['id'=>$id,'date'=>$date,'time'=>$time,'category'=>trim($d[5]),'competition'=>trim($d[6]),'home'=>$clubs[0]['name'],'away'=>$clubs[1]['name'],'venue'=>preg_replace('/^Spielstätte:\s*/u','',self::plain(self::capture('~<tr\b[^>]*class="[^"]*\brow-venue\b[^"]*"[^>]*>([\s\S]*?)</tr>~i',$chunk))),'location'=>$clubs[0]['ours']&&$clubs[1]['ours']?'Vereinsduell':($clubs[0]['ours']?'Heim':'Auswärts'),'status'=>$status,'url'=>self::plain($url),'resultUnavailable'=>!$status&&(str_contains($homeScore??'','?')||str_contains($awayScore??'','?'))];
    foreach(['home','away'] as $i=>$side)if($clubs[$i]['logo'])$m[$side.'Logo']=$clubs[$i]['logo'];
    if(!$status&&$homeScore!==null&&$awayScore!==null&&preg_match('/^\d{1,3}$/D',$homeScore)&&preg_match('/^\d{1,3}$/D',$awayScore))$m['result']=['home'=>(int)$homeScore,'away'=>(int)$awayScore,'confirmed'=>str_contains($scoreHtml,'icon-verified')];
    $matches[]=$m;
   }catch(\Throwable){$omittedRows++;}
  }
  if($omittedRows&&!$matches)throw new \RuntimeException('No readable match rows');$unique=[];foreach($matches as $m)if($m['date']>=$from&&$m['date']<=$to)$unique[$m['id']]=$m;$matches=array_values($unique);usort($matches,fn($a,$b)=>strcmp($a['date'].' '.$a['time'],$b['date'].' '.$b['time']));return compact('matches','provisional','omittedRows');
 }
 public static function load(string $from,string $to):array{
  if(random_int(1,20)===1)Cache::prune();
  return Cache::remember('matches-'.$from.'-'.$to,300,86400,function()use($from,$to){
   $html=Http::get(self::source($from,$to),2000000,15);$fonts=[];
   preg_match_all('/<span\b[^>]*data-obfuscation="([a-z0-9]+)"[^>]*class="score-(?:left|right)"/',$html,$ids);$ids=array_unique($ids[1]);if(count($ids)>8)throw new \RuntimeException('Unexpected font count');
   foreach($ids as $id){try{$fonts[$id]=Cache::remember('font-'.$id,86400,604800,fn()=>ScoreFont::decode(Http::get('https://www.fussball.de/export.fontface/-/format/ttf/id/'.$id.'/type/font',1000000,15)),false);}catch(\Throwable){/* Unknown glyphs remain unknown; never guess a score. */}}
   return array_merge(self::parse($html,$from,$to),['from'=>$from,'to'=>$to,'retrievedAt'=>gmdate('c'),'stale'=>false]);
  });
 }
}
