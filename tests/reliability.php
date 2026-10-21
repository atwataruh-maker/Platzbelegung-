<?php
declare(strict_types=1);
require __DIR__.'/../server/Core.php';require __DIR__.'/../server/Planning.php';require __DIR__.'/../server/Matches.php';require __DIR__.'/../server/ScoreFont.php';
use Vfl\{App,Cache,Matches};
function check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$dir=sys_get_temp_dir().'/vfl-reliability-'.bin2hex(random_bytes(6));mkdir($dir.'/cache',0700,true);$config=new ReflectionProperty(App::class,'config');$config->setValue(null,['storage_path'=>$dir]);
try{
 $font=base64_decode(trim(stream_get_contents(STDIN)),true);check($font!==false,'Fixture');
 $html='<span class="score-left" data-obfuscation="testfont">&#xE001;</span><span data-obfuscation="testfont" class="score-right">&#xE002;</span>';
 check(Matches::fontIds($html)===['testfont'],'Font attributes must be order independent');
 $calls=0;$fonts=Matches::resolveFonts($html,function()use(&$calls,$font){if(++$calls===1)throw new RuntimeException('Transient timeout');return $font;});
 check($calls===2&&$fonts['testfont']["\u{E001}"]==='0','Transient failure must recover');
 Matches::resolveFonts($html,function(){throw new RuntimeException('Must use cached font');});
 $calls=0;Matches::resolveFonts(str_replace('testfont','blockedfont',$html),function()use(&$calls){$calls++;throw new RuntimeException('Forbidden',403);});check($calls===1,'Do not retry access denial');
 $calls=0;check(Matches::resolveFonts(str_replace('testfont','failedfont',$html),function()use(&$calls){$calls++;throw new RuntimeException('Offline');})===[],'Unknown result stays unknown');check($calls===2,'Bound retries');
 check(isset(Matches::resolveFonts(str_replace('testfont','failedfont',$html),fn()=>$font)['failedfont']),'Failed font must not be cached as success');
 $ttl=fn($d)=>$d['partial']?30:300;
 Cache::remember('partial',$ttl,86400,fn()=>['partial'=>true]);$path=$dir.'/cache/partial.json';$saved=json_decode(file_get_contents($path),true);$saved['time']=time()-31;file_put_contents($path,json_encode($saved));
 $r=Cache::remember('partial',$ttl,86400,function($old){check($old['partial'],'Previous result passed to loader');return ['partial'=>false];});check(!$r['partial'],'Partial result retry after 30s');
 $saved=json_decode(file_get_contents($path),true);$saved['time']=time()-31;file_put_contents($path,json_encode($saved));Cache::remember('partial',$ttl,86400,fn()=>throw new RuntimeException('Should stay fresh for 5m'));
 $m=['id'=>'game','date'=>'2026-10-18','time'=>'15:00','home'=>'VfL','away'=>'Gast','status'=>'','resultUnavailable'=>true];$old=['matches'=>[$m+['result'=>['home'=>2,'away'=>0,'confirmed'=>true]]],'retrievedAt'=>gmdate('c',time()-600)];
 $r=Matches::keepKnownResults(['matches'=>[$m]],$old);check($r['matches'][0]['resultStale']&&$r['matches'][0]['result']['home']===2,'Preserve known result with stale label');
 check($r['matches'][0]['resultRetrievedAt']===$old['retrievedAt'],'Do not reset result age');
 foreach([['status'=>'Ausfall'],['time'=>'16:00'],['date'=>'2026-10-19'],['away'=>'Anderer Gast']] as $change){$r=Matches::keepKnownResults(['matches'=>[array_replace($m,$change)]],$old);check(!isset($r['matches'][0]['result']),'Never carry a result into changed/cancelled fixture');}
 $old['retrievedAt']=gmdate('c',time()-86401);check(!isset(Matches::keepKnownResults(['matches'=>[$m]],$old)['matches'][0]['result']),'Expire old scores');
 check(!isset(Matches::keepKnownResults(['matches'=>[$m]],null)['matches'][0]['result']),'Never invent a score');
 echo "PASS: font retries/cache/recovery, attribute order, partial cache TTL, bounded failures, stale score safety.\n";
}finally{foreach(glob($dir.'/cache/*') as $f)unlink($f);rmdir($dir.'/cache');rmdir($dir);}
