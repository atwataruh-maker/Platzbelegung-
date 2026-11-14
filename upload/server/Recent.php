<?php
declare(strict_types=1);
namespace Vfl;
final class Recent {
 public static function window(?\DateTimeImmutable $now=null):array{
  $now=($now??new \DateTimeImmutable('now'))->setTimezone(new \DateTimeZone('Europe/Berlin'));
  $since=$now->setTimestamp($now->getTimestamp()-48*3600);
  return ['from'=>$since->format('Y-m-d'),'to'=>$now->format('Y-m-d'),'since'=>$since->format('c'),'until'=>$now->format('c')];
 }
 public static function select(array $schedule,array $window):array{
  $min=strtotime($window['since']);$max=strtotime($window['until']);$matches=[];$unknown=0;
  foreach($schedule['matches'] as $m){
   if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D',$m['time'])){$unknown++;continue;}
   $at=new \DateTimeImmutable($m['date'].' '.$m['time'],new \DateTimeZone('Europe/Berlin'));
   if($at->getTimestamp()<$min||$at->getTimestamp()>$max)continue;
   $m['kickoffAt']=$at->format('c');$matches[]=$m;
  }
  usort($matches,fn($a,$b)=>strcmp($b['kickoffAt'],$a['kickoffAt']));
  return array_merge($schedule,$window,['matches'=>$matches,'omittedRows'=>($schedule['omittedRows']??0)+$unknown]);
 }
 public static function load():array{$window=self::window();return self::select(Matches::load($window['from'],$window['to']),$window);}
}
