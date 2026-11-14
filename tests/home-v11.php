<?php
declare(strict_types=1);
require __DIR__.'/../server/Recent.php';require __DIR__.'/../server/Bomber.php';
function check(bool $value,string $label):void{if(!$value)throw new RuntimeException($label);}
$now=new DateTimeImmutable('2026-11-14T19:00:00+01:00');$window=Vfl\Recent::window($now);
check($window['since']==='2026-11-12T19:00:00+01:00','rolling cutoff');
$matches=[];foreach([['old','2026-11-12','18:59'],['edge','2026-11-12','19:00'],['today','2026-11-14','18:00'],['future','2026-11-14','20:00'],['unknown','2026-11-14','Zeit offen']] as [$id,$date,$time])$matches[]=compact('id','date','time');
$selected=Vfl\Recent::select(['matches'=>$matches],$window);
check(array_column($selected['matches'],'id')===['today','edge'],'exact filtering and order');
$dst=Vfl\Recent::window(new DateTimeImmutable('2026-10-25T12:00:00+01:00'));check($dst['since']==='2026-10-23T13:00:00+02:00','DST exact hours');
$html='<iframe id="pdf" src="/bomber/Bomber - 13.Spieltag_2027.pdf"></iframe><a href="/bomber/new-looking.pdf">Ignore archive order</a>';
$issue=Vfl\Bomber::parse($html);check($issue['url']==='https://www.vfl-ramsdorf.de/bomber/Bomber%20-%2013.Spieltag_2027.pdf','current iframe and encoding');check($issue['label']==='13. Spieltag · Saison 2026/27','issue label');
foreach(['https://evil.test/bomber/test.pdf','//evil.test/test.pdf','/bomber/../test.pdf','/bomber/%2e%2e%2ftest.pdf','/bomber/test.pdf?x=1','/bomber/test.pdf#x','https://www.vfl-ramsdorf.de:443/bomber/test.pdf','/bomber/a%00.pdf','/bomber/test.html'] as $src){$failed=false;try{Vfl\Bomber::parse('<iframe id="pdf" src="'.$src.'"></iframe>');}catch(Throwable){$failed=true;}check($failed,'reject '.$src);}
echo "PASS: PHP rolling results, DST, current Bomber iframe, issue label, encoding and source restrictions.\n";
