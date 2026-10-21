<?php
declare(strict_types=1);
namespace Vfl;
final class Cabins {
 private static function concurrent(array $a,array $b):bool{return $a['start']<$b['end']&&$b['start']<$a['end'];}
 private static function gender(string $s):string{$f=(bool)preg_match('/Juniorinnen|Damen|Frauen/i',$s);$m=(bool)preg_match('/Junioren|Herren|Männer/i',$s);return $f===$m?'unknown':($f?'female':'male');}
 private static function youth(string $s):bool{if(!preg_match('/^(.+)-Junior(?:en|innen)$/Di',$s,$m))return false;foreach(preg_split('/\s*[\/-]\s*/',$m[1]) as $part)if(!preg_match('/^[A-G]\d*$/Di',$part))return false;return true;}
 private static function share(array $a,array $b):bool{return $a['shareEligible']&&$b['shareEligible']&&$a['gender']!=='unknown'&&$a['gender']===$b['gender']&&$a['guest']===$b['guest']&&!$a['fixed']&&!$b['fixed'];}
 private static function women(array $b):bool{return !$b['guest']&&$b['gender']==='female'&&!$b['shareEligible']&&(($b['category']??'')==='Frauen'||preg_match('/\b(Damen|Frauen)\b/i',$b['team']));}
 private static function rooms(array $b):array{if($b['fixed'])return [$b['fixed']];if(self::women($b))return ['home1','home2'];return $b['guest']?['away1','shared']:['shared','home1','home2','away1'];}
 private static function ownTeam(string $team,string $category,array $names):?string{
  if(!preg_match('/^VfL\s+Ramsdorf(?:\s+(I|II|III|IV|[1-9]\d*))?$/Di',$team,$m))return null;
  $suffix=strtoupper($m[1]??'1');$n=['I'=>1,'II'=>2,'III'=>3,'IV'=>4][$suffix]??(int)$suffix;
  if(preg_match('/^([A-G])-(Junioren|Juniorinnen)$/D',$category,$y))$choices=["$y[1]$n-$y[2]",...($n===1?["$y[1]-$y[2]"]:[])];
  else $choices=$category==='Herren'?["$n. Herren"]:($category==='Frauen'&&$n===1?['VfL Ramsdorf Damen']:($category==='Altherren'&&$n===1?['Alte Herren']:[]));
  foreach($choices as $c)if(in_array($c,$names,true))return $c;return null;
 }
 public static function allocate(array $allocation,?array $schedule):array{
  $requests=[];$trainingNames=array_column(array_filter($allocation['bookings'],fn($b)=>$b['kind']==='training'),'title');
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
  foreach($merged as $b){if($b['gender']==='unknown')$unknown[]=$b;elseif($b['fixed']){$b['room']=$b['fixed'];$fixed[]=$b;}}
  foreach($merged as $b)if(!$b['fixed']&&$b['gender']!=='unknown'){
   $rooms=array_values(array_filter(self::rooms($b),function($room)use($b,$fixed){foreach($fixed as $f)if($f['room']===$room&&self::concurrent($b,$f))return false;return true;}));
   $pending[]=['b'=>$b,'rooms'=>$rooms,'game'=>(bool)array_diff($b['activities'],['Training'])];
  }
  usort($pending,fn($a,$b)=>(int)$b['b']['guest']<=>(int)$a['b']['guest']?:(int)$b['game']<=>(int)$a['game']?:count($a['rooms'])<=>count($b['rooms'])?:$a['b']['start']<=>$b['b']['start']?:strcmp($a['b']['id'],$b['b']['id']));
  $weight=count($pending)+1;$scores=array_map(fn($p)=>(int)$p['b']['guest']*$weight*$weight+(int)$p['game']*$weight+1,$pending);$remaining=array_fill(0,count($pending)+1,0);for($i=count($pending)-1;$i>=0;$i--)$remaining[$i]=$remaining[$i+1]+$scores[$i];
  $best=[];$bestScore=-1;$bestCost=PHP_INT_MAX;$nodes=0;
  $search=function($i,$placed,$picked,$score,$cost)use(&$search,&$best,&$bestScore,&$bestCost,&$nodes,$pending,$scores,$remaining){
   if(++$nodes>50000)return;$maximum=$score+$remaining[$i];if($maximum<$bestScore||($maximum===$bestScore&&$cost>=$bestCost))return;
   if($i===count($pending)){$best=$picked;$bestScore=$score;$bestCost=$cost;return;}
   $b=$pending[$i]['b'];foreach($pending[$i]['rooms'] as $room){$neighbours=array_values(array_filter($placed,fn($p)=>$p['room']===$room&&self::concurrent($b,$p)));$ok=true;foreach($neighbours as $n)if(!self::share($b,$n)){$ok=false;break;}
    if($ok)$search($i+1,[...$placed,array_replace($b,['room'=>$room])],[...$picked,$room],$score+$scores[$i],$cost+array_search($room,self::rooms($b),true)+count($neighbours)*4);
   }$search($i+1,$placed,[...$picked,null],$score,$cost);
  };$search(0,[],[],0,0);
  $assigned=$fixed;foreach($pending as $i=>$p)$assigned[]=array_replace($p['b'],['room'=>$best[$i]??null]);$assigned=[...$assigned,...$unknown];$conflicts=[];
  foreach($assigned as $i=>$b){if($b['room']){$assigned[$i]['sharingWith']=array_values(array_column(array_filter($assigned,fn($o)=>$o['id']!==$b['id']&&$o['room']===$b['room']&&self::concurrent($b,$o)),'team'));continue;}
   $reason=$b['gender']==='unknown'?'Geschlechtszuordnung fehlt in der Quelle. Bitte mit der Kabinenkoordination klären.':(self::women($b)?'Für die Damen ist weder die 1. noch die 2. Heimkabine im gesamten Zeitraum allein verfügbar. Bitte mit der Kabinenkoordination abstimmen.':'Keine konfliktfreie Kabinenzuordnung für den gesamten Zeitraum ermittelt. Bitte mit der Kabinenkoordination abstimmen.');
   $assigned[$i]['reason']=$reason;$conflicts[]=$b['team'].(isset($b['category'])?' · '.$b['category']:'').' · '.Planning::clock($b['start']).'–'.Planning::clock($b['end']).': '.$reason;
  }
  usort($assigned,fn($a,$b)=>$a['start']<=>$b['start']?:strcmp($a['team'],$b['team'])?:strcmp($a['id'],$b['id']));return ['bookings'=>$assigned,'confirmed'=>$allocation['automatic'],'conflicts'=>$conflicts];
 }
}
