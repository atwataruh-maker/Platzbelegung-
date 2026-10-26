<?php
declare(strict_types=1);
namespace Vfl;
final class Cabins {
 private static function concurrent(array $a,array $b):bool{return $a['start']<$b['end']&&$b['start']<$a['end'];}
 private static function gender(string $s):string{$f=(bool)preg_match('/Juniorinnen|Damen|Frauen/i',$s);$m=(bool)preg_match('/Junioren|Herren|Männer/i',$s);return $f===$m?'unknown':($f?'female':'male');}
 private static function youth(string $s):bool{if(!preg_match('/^(.+)-Junior(?:en|innen)$/Di',$s,$m))return false;foreach(preg_split('/\s*[\/-]\s*/',$m[1]) as $part)if(!preg_match('/^[A-G]\d*$/Di',$part))return false;return true;}
 private static function share(array $a,array $b,string $date,string $room):bool{
  $e3=fn($t)=>$t['team']==='E3-Junioren'&&!$t['guest']&&!$t['fixed']&&$t['gender']==='male'&&$t['activities']===['Training'];
  if($date>='2026-10-29'&&(new \DateTimeImmutable($date.'T12:00:00Z'))->format('N')==='4'&&$room==='away1'&&((self::groundsUser($a)&&$e3($b))||(self::groundsUser($b)&&$e3($a))))return true;
  return $a['shareEligible']&&$b['shareEligible']&&$a['gender']!=='unknown'&&$a['gender']===$b['gender']&&$a['guest']===$b['guest']&&!$a['fixed']&&!$b['fixed'];}
 private static function women(array $b):bool{return !$b['guest']&&$b['gender']==='female'&&!$b['shareEligible']&&(($b['category']??'')==='Frauen'||preg_match('/\b(Damen|Frauen)\b/i',$b['team']));}
 private static function groundsUser(array $b):bool{return in_array($b['team'],['Nordvelen','SF Nordvelen'],true)&&!$b['guest']&&$b['activities']===['Training'];}
 private static function preferredRoom(array $b,string $date):?string{return self::groundsUser($b)?'away1':(self::thursdayRoom($b,$date)??self::wednesdayRoom($b,$date));}
 private static function roomName(string $room):string{return ['home1'=>'1. Heimkabine','home2'=>'2. Heimkabine','shared'=>'3. Heim- / 2. Auswärtskabine','away1'=>'1. Auswärtskabine'][$room];}
 private static function thursdayRoom(array $b,string $date):?string{
  if($date<'2026-10-29'||(new \DateTimeImmutable($date.'T12:00:00Z'))->format('N')!=='4'||$b['guest']||$b['fixed']||$b['activities']!==['Training'])return null;
  if($b['team']==='E3-Junioren')return 'away1';
  if(in_array($b['team'],['E1-Junioren','A-Junioren'],true))return 'home2';return in_array($b['team'],['B-Junioren','C-Junioren'],true)?'shared':null;
 }
 private static function wednesdayRoom(array $b,string $date):?string{
  if($date<'2026-10-28'||(new \DateTimeImmutable($date.'T12:00:00Z'))->format('N')!=='3'||$b['guest']||$b['fixed']||$b['activities']!==['Training'])return null;
  if(in_array($b['team'],['C-Juniorinnen','D1-Juniorinnen'],true))return 'home1';
  if(in_array($b['team'],['D1-Junioren','E2-Junioren'],true))return 'shared';return null;
 }
 private static function rooms(array $b,string $date):array{if($b['fixed'])return [$b['fixed']];if(self::women($b))return ['home1','home2'];$regular=self::groundsUser($b)?['away1','shared','home2','home1']:($b['guest']?['away1','shared']:['shared','home1','home2','away1']);$preferred=self::preferredRoom($b,$date);return $preferred?[$preferred,...array_values(array_filter($regular,fn($r)=>$r!==$preferred))]:$regular;}
 private static function ownTeam(string $team,string $category,array $names):?string{
  if(!preg_match('/^VfL\s+Ramsdorf(?:\s+(I|II|III|IV|[1-9]\d*))?$/Di',$team,$m))return null;
  $suffix=strtoupper($m[1]??'1');$n=['I'=>1,'II'=>2,'III'=>3,'IV'=>4][$suffix]??(int)$suffix;
  if(preg_match('/^([A-G])-(Junioren|Juniorinnen)$/D',$category,$y))$choices=["$y[1]$n-$y[2]",...($n===1?["$y[1]-$y[2]"]:[])];
  else $choices=$category==='Herren'?["$n. Herren"]:($category==='Frauen'&&$n===1?['VfL Ramsdorf Damen']:($category==='Altherren'&&$n===1?['Alte Herren']:[]));
  foreach($choices as $c)if(in_array($c,$names,true))return $c;return null;
 }
 public static function allocate(array $allocation,?array $schedule,bool $includeChanges=true):array{
  $date=$allocation['date'];$requests=[];$trainingNames=array_column(array_filter($allocation['bookings'],fn($b)=>$b['kind']==='training'),'title');
  foreach($allocation['bookings'] as $b){
   if($b['kind']==='reserve')continue;$segments=$b['segments']?:($b['original']??[]);if(!$segments)continue;
   $start=min(array_column($segments,'start'));$end=max(array_column($segments,'end'));
   if($b['kind']==='training'){
    $groups=preg_match('/Junioren/i',$b['title'])&&preg_match('/Juniorinnen/i',$b['title'])?preg_split('/\s+\/\s+/',$b['title']):[$b['title']];
    foreach($groups as $i=>$team)$requests[]=['id'=>$b['id'].'-cabin-'.$i,'team'=>$team,'clubTeam'=>$team,'gender'=>self::gender($team),'start'=>max(0,$start-15),'end'=>min(1440,$end+15),'room'=>null,'fixed'=>$team==='1. Herren'?'home1':($team==='2. Herren'?'home2':null),'guest'=>false,'shareEligible'=>self::youth($team),'activities'=>['Training'],'sourceIds'=>[$b['id']]];
   }else{
    $match=null;foreach($schedule['matches']??[] as $m)if('match-'.$m['id']===$b['id']){$match=$m;break;}if(!$match||!isset($b['teams']))continue;
    foreach(['home','away'] as $side){
     $ours=$side==='home'||$match['location']==='Vereinsduell';$team=$b['teams'][$side];$fixed=null;
     if($ours&&$match['category']==='Herren'&&preg_match('/^VfL\s+Ramsdorf(?:\s+(I|II|1|2))?$/Di',$team,$f))$fixed=in_array(strtoupper($f[1]??'I'),['II','2'],true)?'home2':'home1';
     $clubTeam=$fixed==='home1'?'1. Herren':($fixed==='home2'?'2. Herren':($ours?self::ownTeam($team,$match['category'],$trainingNames):null));
     $r=['id'=>$b['id'].'-'.$side,'team'=>$clubTeam??$team,'category'=>$match['category'],'gender'=>self::gender($match['category']),'start'=>$start,'end'=>min(1440,$end+15),'room'=>null,'fixed'=>$fixed,'guest'=>!$ours,'shareEligible'=>self::youth($match['category']),'activities'=>[$side==='home'?'Heimspiel':($ours?'Vereinsduell':'Gastspiel')],'sourceIds'=>[$b['id']]];if($clubTeam!==null)$r['clubTeam']=$clubTeam;$requests[]=$r;
    }
   }
  }
  usort($requests,fn($a,$b)=>$a['start']<=>$b['start']?:strcmp($a['id'],$b['id']));$merged=[];
  foreach($requests as $r){$same=null;if(isset($r['clubTeam']))foreach($merged as $i=>$b)if(($b['clubTeam']??null)===$r['clubTeam']&&$b['gender']===$r['gender']&&self::concurrent($b,$r)){$same=$i;break;}
   if($same!==null){$b=&$merged[$same];$b['start']=min($b['start'],$r['start']);$b['end']=max($b['end'],$r['end']);if(!isset($b['category'])&&isset($r['category']))$b['category']=$r['category'];$b['shareEligible']=$b['shareEligible']&&$r['shareEligible'];$b['activities']=array_values(array_unique([...$b['activities'],...$r['activities']]));$b['sourceIds']=[...$b['sourceIds'],...$r['sourceIds']];unset($b);}else $merged[]=$r;
  }
  $fixed=[];$pending=[];$unknown=[];
  foreach($merged as $b){if($b['gender']==='unknown'&&!self::groundsUser($b))$unknown[]=$b;elseif($b['fixed']){$b['room']=$b['fixed'];$fixed[]=$b;}}
  foreach($merged as $b)if(!$b['fixed']&&($b['gender']!=='unknown'||self::groundsUser($b))){
   $rooms=array_values(array_filter(self::rooms($b,$date),function($room)use($b,$fixed){foreach($fixed as $f)if($f['room']===$room&&self::concurrent($b,$f))return false;return true;}));
   $pending[]=['b'=>$b,'rooms'=>$rooms,'game'=>(bool)array_diff($b['activities'],['Training'])];
  }
  usort($pending,fn($a,$b)=>(int)$b['b']['guest']<=>(int)$a['b']['guest']?:(int)$b['game']<=>(int)$a['game']?:count($a['rooms'])<=>count($b['rooms'])?:$a['b']['start']<=>$b['b']['start']?:strcmp($a['b']['id'],$b['b']['id']));
  $weight=count($pending)+1;$scores=array_map(fn($p)=>(int)$p['b']['guest']*$weight*$weight+(int)$p['game']*$weight+1,$pending);$remaining=array_fill(0,count($pending)+1,0);for($i=count($pending)-1;$i>=0;$i--)$remaining[$i]=$remaining[$i+1]+$scores[$i];
  $preferencePenalty=4*(count($pending)+1)**2;$guestPreferencePenalty=(count($pending)+1)*($preferencePenalty+4*(count($pending)+1));$best=[];$bestScore=-1;$bestCost=PHP_INT_MAX;$nodes=0;
  $search=function($i,$placed,$picked,$score,$cost)use(&$search,&$best,&$bestScore,&$bestCost,&$nodes,$pending,$scores,$remaining,$date,$preferencePenalty,$guestPreferencePenalty){
   if(++$nodes>50000)return;$maximum=$score+$remaining[$i];if($maximum<$bestScore||($maximum===$bestScore&&$cost>=$bestCost))return;
   if($i===count($pending)){$best=$picked;$bestScore=$score;$bestCost=$cost;return;}
   $b=$pending[$i]['b'];foreach($pending[$i]['rooms'] as $room){$neighbours=array_values(array_filter($placed,fn($p)=>$p['room']===$room&&self::concurrent($b,$p)));$ok=true;foreach($neighbours as $n)if(!self::share($b,$n,$date,$room)){$ok=false;break;}
    if($ok)$search($i+1,[...$placed,array_replace($b,['room'=>$room])],[...$picked,$room],$score+$scores[$i],$cost+($b['guest']&&$room!=='away1'?$guestPreferencePenalty:0)+array_search($room,self::rooms($b,$date),true)+count($neighbours)*4+(self::preferredRoom($b,$date)&&$room!==self::preferredRoom($b,$date)?$preferencePenalty:0));
   }$search($i+1,$placed,[...$picked,null],$score,$cost);
  };$search(0,[],[],0,0);
  $assigned=$fixed;foreach($pending as $i=>$p)$assigned[]=array_replace($p['b'],['room'=>$best[$i]??null]);$assigned=[...$assigned,...$unknown];foreach($assigned as &$entry)if(self::groundsUser($entry)){$entry['team']='Nordvelen';$entry['playerCount']=10;$entry['teamCount']=1;}unset($entry);$conflicts=[];
  foreach($assigned as $i=>$b){if($b['room']){$assigned[$i]['sharingWith']=array_values(array_column(array_filter($assigned,fn($o)=>$o['id']!==$b['id']&&$o['room']===$b['room']&&self::concurrent($b,$o)),'team'));continue;}
   $reason=$b['gender']==='unknown'&&!self::groundsUser($b)?'Geschlechtszuordnung fehlt in der Quelle. Bitte mit der Kabinenkoordination klären.':(self::women($b)?'Für die Damen ist weder die 1. noch die 2. Heimkabine im gesamten Zeitraum allein verfügbar. Bitte mit der Kabinenkoordination abstimmen.':'Keine konfliktfreie Kabinenzuordnung für den gesamten Zeitraum ermittelt. Bitte mit der Kabinenkoordination abstimmen.');
   $assigned[$i]['reason']=$reason;$conflicts[]=$b['team'].(isset($b['category'])?' · '.$b['category']:'').' · '.Planning::clock($b['start']).'–'.Planning::clock($b['end']).': '.$reason;
  }
  usort($assigned,fn($a,$b)=>$a['start']<=>$b['start']?:strcmp($a['team'],$b['team'])?:strcmp($a['id'],$b['id']));$notes=[];$announced=[];
  if($includeChanges&&array_filter($allocation['bookings'],fn($b)=>$b['kind']==='match')){
   $baseline=self::allocate(array_replace($allocation,['bookings'=>array_values(array_filter($allocation['bookings'],fn($b)=>$b['kind']==='training'))]),null,false);
   foreach($assigned as $b){$old=null;foreach($baseline['bookings'] as $o)if($o['id']===$b['id']){$old=$o;break;}
    if($b['activities']!==['Training']||!($old['room']??null)||!$b['room']||$b['room']===$old['room'])continue;
    $notes[]=$b['team'].' · '.Planning::clock($b['start']).'–'.Planning::clock($b['end']).': Wegen eines Spiels von '.self::roomName($old['room']).' in '.self::roomName($b['room']).' verlegt.';$announced[$b['id']]=true;
   }
  }
  if($includeChanges)foreach($assigned as $b){$preferred=self::preferredRoom($b,$date);if($preferred&&$b['room']&&$b['room']!==$preferred&&!isset($announced[$b['id']]))$notes[]=$b['team'].' · '.Planning::clock($b['start']).'–'.Planning::clock($b['end']).': Wegen anderer Belegungen von '.self::roomName($preferred).' in '.self::roomName($b['room']).' verlegt.';}

  return array_merge(['bookings'=>$assigned,'confirmed'=>$allocation['automatic'],'conflicts'=>$conflicts],$notes?['notes'=>$notes]:[]);
 }
}
