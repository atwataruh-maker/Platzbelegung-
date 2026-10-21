<?php
declare(strict_types=1);
namespace Vfl;
final class Planning {
 const FRONT=['k1a','k1b'], BACK=['k2a','k2b'], TURF=['k1a','k1b','k2a','k2b'], MAIN=['main1','main2'];
 public static function clock(int $n):string{return sprintf('%02d:%02d',intdiv($n,60),$n%60);}
 public static function minutes(string $s):int{[$h,$m]=explode(':',$s);return (int)$h*60+(int)$m;}
 public static function validDate(string $s):bool{return preg_match('/^(20\d{2}|2100)-(\d{2})-(\d{2})$/D',$s,$m)===1&&checkdate((int)$m[2],(int)$m[3],(int)$m[1]);}
 public static function label(array $f):string{
  if(!$f)return 'Keine freie Fläche';$parts=[];
  if(!array_diff(self::MAIN,$f))$parts[]='Hauptplatz komplett';else foreach(['main1'=>'Hauptplatz · Hälfte 1','main2'=>'Hauptplatz · Hälfte 2'] as $id=>$name)if(in_array($id,$f,true))$parts[]=$name;
  if(!array_diff(['r1','r2','r3'],$f))$parts[]='Trainingsrasen komplett';else foreach(['r1'=>'Rasen 1/3','r2'=>'Rasen 2/3','r3'=>'Rasen 3/3','corner'=>'Rasen · Reserve-Ecke'] as $id=>$name)if(in_array($id,$f,true))$parts[]=$name;
  if(!array_diff(self::TURF,$f))$parts[]='Kunstrasen komplett';else foreach([[self::FRONT,'Kunstrasen vorne'],[self::BACK,'Kunstrasen hinten']] as [$g,$name]){ $n=count(array_intersect($g,$f));if($n)$parts[]=$name.($n===1?' · Viertelfeld':''); }
  return implode(' + ',$parts);
 }
 public static function trainings(string $date):array{$all=json_decode(file_get_contents(__DIR__.'/training.json'),true,512,JSON_THROW_ON_ERROR);$day=(int)(new \DateTimeImmutable($date))->format('w');return array_values(array_filter($all,fn($t)=>$t['day']===$day&&(!isset($t['activeFrom'])||$date>=$t['activeFrom'])));}
 private static function clash(array $segments,array $bookings):array{return array_values(array_filter($bookings,function($b)use($segments){foreach($b['segments'] as $s)foreach($segments as $t)if($s['start']<$t['end']&&$t['start']<$s['end']&&array_intersect($s['fields'],$t['fields']))return true;return false;}));}
 private static function seg(int $start,int $end,array $fields):array{return compact('start','end','fields');}
 private static function booking(array $t,array $segments):array{return ['id'=>$t['id'],'kind'=>'training','title'=>$t['team'],'segments'=>$segments];}
 private static function choices(array $t):array{
  $grass=$t['small']?[['r1'],['r2'],['r3'],['corner']]:[['r1'],['r2','r3']];
  $onGrass=(bool)array_intersect($t['segments'][0]['fields'],['r1','r2','r3','corner']);
  $options=$onGrass?[...$grass,self::FRONT,self::BACK]:[self::FRONT,self::BACK,...$grass];
  if($t['team']==='1. Herren'||preg_match('/Junior(?:en|innen)/i',$t['team']))$options=[...$options,['main1'],['main2']];return $options;
 }
 public static function allocate(string $date,?array $schedule,bool $sourceUsable=true,?array $template=null):array{
  $template??=self::trainings($date);$result=['date'=>$date,'bookings'=>[],'changes'=>[],'conflicts'=>[],'warnings'=>[],'automatic'=>false];
  $originals=array_map(fn($t)=>self::booking($t,$t['segments']),$template);$reserves=[];
  if((new \DateTimeImmutable($date))->format('w')==='5')foreach([[990,1230,self::FRONT],[1050,1080,self::BACK],[1170,1230,self::BACK]] as $i=>[$start,$end,$fields])$reserves[]=['id'=>'reserve-'.$i,'kind'=>'reserve','title'=>'Spielreserve','segments'=>[self::seg($start,$end,$fields)]];
  $inRange=$schedule&&$schedule['from']<=$date&&$schedule['to']>=$date;
  $trusted=$inRange&&$sourceUsable&&!$schedule['stale']&&empty($schedule['omittedRows']);
  if(!$sourceUsable)$result['warnings'][]='PDF-Quelle nicht bestätigt: automatische Verschiebung pausiert.';
  if(!$inRange)$result['warnings'][]='Spieldaten fehlen: Überschneidungen können nicht geprüft werden.';
  elseif($schedule['stale']||!empty($schedule['omittedRows']))$result['warnings'][]='Spieldaten sind veraltet oder unvollständig: automatische Verschiebung pausiert.';
  if($inRange&&$schedule['provisional'])$result['warnings'][]='Die Quelle enthält vorläufige Ansetzungen. Sie werden vorsorglich eingeplant; Änderungen sind möglich.';
  $requests=[];
  foreach($inRange?$schedule['matches']:[] as $m){
   if($m['date']!==$date||$m['location']==='Auswärts'||preg_match('/Ausfall|Absetzung|Nichtantritt|spielfrei/i',$m['status']))continue;
   if(preg_match('/verlegt/i',$m['status'])){
    $has=preg_match('/(\d{2})\.(\d{2})\.(\d{4}) (\d{2}:\d{2})/',$m['status'],$moved);$target=$has?"$moved[3]-$moved[2]-$moved[1]":'';
    if($has&&self::validDate($target)){$result['warnings'][]="$m[home]: verlegt auf $moved[1].$moved[2].$moved[3] um $moved[4].";if($target!==$date)continue;$m['time']=$moved[4];}
    else{$result['warnings'][]="$m[home]: neuer Spieltermin ungeklärt. Automatische Verteilung pausiert.";$trusted=false;continue;}
   }
   if(!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$m['time'])){$result['warnings'][]="$m[home]: Anstoßzeit fehlt. Keine sichere automatische Verteilung möglich.";$trusted=false;continue;}
   preg_match('/^([A-G])-Junior(?:en|innen)$/D',$m['category'],$age);$youth=$age[1]??null;
   if(!$youth&&!in_array($m['category'],['Herren','Frauen','Altherren','Ü-Herren'],true)){$result['warnings'][]="$m[category]: Platzbedarf ungeklärt. Automatische Verteilung pausiert.";$trusted=false;continue;}
   if(str_contains($m['url'],'/spieltag/')){$result['warnings'][]="$m[home]: Spielfest ohne bestätigte Endzeit. Automatische Verteilung pausiert.";$trusted=false;}
   $duration=['G'=>60,'F'=>40,'E'=>50,'D'=>60,'C'=>70,'B'=>80,'A'=>90,'senior'=>90][$youth??'senior'];
   preg_match('/Ramsdorf\s*(III|II|IV|I|\d+)?\s*$/i',$m['home'],$suffix);
   $main=$m['category']==='Herren'&&in_array(strtoupper($suffix[1]??''),['','I','II','III','1','2','3'],true);
   $choices=$main?[self::MAIN]:($youth&&in_array($youth,['G','F','E'],true)?[self::FRONT,self::BACK]:[self::TURF]);
   $requests[]=['m'=>$m,'segment'=>self::seg(max(0,self::minutes($m['time'])-30),min(1440,self::minutes($m['time'])+$duration+30),[]),'choices'=>$choices];
  }
  usort($requests,fn($a,$b)=>count($b['choices'][0])<=>count($a['choices'][0])?:$a['segment']['start']<=>$b['segment']['start']?:strcmp($a['m']['id'],$b['m']['id']));$games=[];
  foreach($requests as ['m'=>$m,'segment'=>$segment,'choices'=>$choices]){
   $free=array_values(array_filter($choices,fn($f)=>!self::clash([array_replace($segment,['fields'=>$f])],$games)));
   usort($free,fn($a,$b)=>count(self::clash([array_replace($segment,['fields'=>$a])],$originals))<=>count(self::clash([array_replace($segment,['fields'=>$b])],$originals)));
   $fields=$free[0]??$choices[0];if(!$free)$result['conflicts'][]="$m[time] · $m[home] – $m[away]: gleichzeitige Spiele benötigen dieselbe Fläche. Vereinsabstimmung nötig.";
   $teams=['home'=>$m['home'],'away'=>$m['away']];foreach(['homeLogo','awayLogo'] as $k)if(isset($m[$k]))$teams[$k]=$m[$k];
   $games[]=['id'=>'match-'.$m['id'],'kind'=>'match','title'=>"$m[home] – $m[away]",'segments'=>[array_replace($segment,['fields'=>$fields])],'kickoff'=>$m['time'],'url'=>$m['url'],'teams'=>$teams];
  }
  $result['automatic']=$trusted;if(!$trusted){$result['bookings']=[...$originals,...$games,...$reserves];return $result;}
  $fixed=$games;$displaced=[];
  foreach($template as $t){$cause=self::clash($t['segments'],$fixed);if($cause)$displaced[]=['t'=>$t,'cause'=>$cause];else $fixed[]=self::booking($t,$t['segments']);}
  $options=[];foreach($displaced as $d){$t=$d['t'];$start=min(array_column($t['segments'],'start'));$end=max(array_column($t['segments'],'end'));$d['start']=$start;$d['end']=$end;$d['options']=array_values(array_filter(self::choices($t),fn($f)=>!self::clash([self::seg($start,$end,$f)],[...$fixed,...$reserves])));$options[]=$d;}
  usort($options,fn($a,$b)=>count($a['options'])<=>count($b['options'])?:$a['start']<=>$b['start']?:strcmp($a['t']['id'],$b['t']['id']));
  $best=[];$bestCount=-1;$bestMain=PHP_INT_MAX;$nodes=0;
  $search=function($i,$placed,$picked,$count,$mainCount)use(&$search,&$best,&$bestCount,&$bestMain,&$nodes,$options){
   $maximum=$count+count($options)-$i;if(++$nodes>50000||$maximum<$bestCount||($maximum===$bestCount&&$mainCount>=$bestMain))return;
   if($i===count($options)){$best=$picked;$bestCount=$count;$bestMain=$mainCount;return;}
   $d=$options[$i];foreach($d['options'] as $fields){$segments=[self::seg($d['start'],$d['end'],$fields)];if(!self::clash($segments,$placed))$search($i+1,[...$placed,self::booking($d['t'],$segments)],[...$picked,$fields],$count+1,$mainCount+(int)(bool)array_intersect($fields,self::MAIN));}
   $search($i+1,$placed,[...$picked,null],$count,$mainCount);
  };$search(0,[],[],0,0);
  foreach($options as $i=>$d){$t=$d['t'];$fields=$best[$i]??null;$time=self::clock($d['start']).'–'.self::clock($d['end']);$from=implode(' / ',array_unique(array_map(fn($s)=>self::label($s['fields']),$t['segments'])));$reason=implode('; ',array_map(fn($b)=>($b['kind']==='match'?'Spiel: ':'Training: ').$b['title'],$d['cause']));
   if($fields){$fixed[]=array_merge(self::booking($t,[self::seg($d['start'],$d['end'],$fields)]),['original'=>$t['segments'],'moved'=>true,'reason'=>$reason]);$result['changes'][]=['team'=>$t['team'],'time'=>$time,'from'=>$from,'to'=>self::label($fields),'reason'=>$reason];}
   else{$result['conflicts'][]="$t[team] · $time: keine passende Ausweichfläche für die gesamte Einheit. $reason.";$fixed[]=array_merge(self::booking($t,[]),['original'=>$t['segments'],'reason'=>$reason]);}
  }
  $result['bookings']=[...$fixed,...$reserves];return $result;
 }
}
