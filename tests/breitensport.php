<?php
declare(strict_types=1);
require __DIR__.'/../server/Breitensport.php';
function check(bool $value,string $label):void{if(!$value)throw new RuntimeException($label);}
$list='<div class="kurs-karte"><img src="/img/breitensport/Line_Dance.png" class="kurs-karte-bild"><div class="kurs-karte-text"><h3><a href="/breitensport/kurs/56">Line Dance - Level I</a></h3><p>Ab 5.11.-17.12.26
jeweils donnerstags, 16.30h.</p></div></div>
<div class="kurs-karte"><img src="https://evil.test/x.png"><div class="kurs-karte-text"><h3><a href="/breitensport/kurs/48">Fit bis ins Hohe Alter</a></h3><p>Fortlaufender Kurs, immer mittwochs nachmittags</p></div></div>
<div class="kurs-karte"><div class="kurs-karte-text"><h3><a href="https://evil.test/breitensport/kurs/9">Fremd</a></h3></div></div>
<div class="kurs-karte"><img src="/img/../secret.png"><div class="kurs-karte-text"><h3><a href="/breitensport/kurs/54">Dancing Fit I</a></h3><p>Jeweils dienstags 17-18 Uhr
27.10.26- 22.12.26</p></div></div>';
$cards=Vfl\Breitensport::parseList($list);
check(array_column($cards,'id')===[56,48,54],'course links from the club website only');
check($cards[0]['image']==='https://www.vfl-ramsdorf.de/img/breitensport/Line_Dance.png','club image');
check($cards[1]['image']===null&&$cards[2]['image']===null,'foreign or traversing image rejected');
check($cards[0]['teaser']===['Ab 5.11.-17.12.26','jeweils donnerstags, 16.30h.'],'teaser lines');
$detail='<h1>Line Dance - Level I</h1><div class="kurs-text"><p>Ab 5.11.-17.12.26</p><p>jeweils donnerstags, 16.30h.</p></div><div class="kurs-info">
<div class="kurs-info-zeile"><div><div class="kurs-info-label">Kursleiter</div>Sabine Scheer</div></div>
<div class="kurs-info-zeile"><div><div class="kurs-info-label">Startdatum</div>05.11.2026</div></div>
<div class="kurs-info-zeile"><div><div class="kurs-info-label">Einheiten</div>7</div></div>
<div class="kurs-info-zeile"><div><div class="kurs-info-label">Ort</div>Sporthalle -  In der Worthe</div></div>
<div class="kurs-info-zeile"><div><div class="kurs-info-label">Preis</div>Mitglieder: 28 €<br>Nicht-Mitglieder: 35,50 €</div></div>
<div class="kurs-info-zeile"><div><div class="kurs-info-label">Verfügbare Plätze</div>2</div></div>
<a href="/breitensport/kurs/56/buchen">Kurs buchen</a></div>';
$c=Vfl\Breitensport::merge($cards[0],Vfl\Breitensport::parseCourse($detail,56));
check($c['leader']==='Sabine Scheer'&&$c['startDate']==='2026-11-05'&&$c['endDate']==='2026-12-17'&&$c['units']===7,'course facts and end date from teaser');
check($c['priceMember']===28.0&&$c['priceGuest']===35.5,'member and guest prices');
check($c['seatsLeft']===2&&$c['venue']==='Sporthalle · In der Worthe','seats and venue');
check($c['bookingUrl']==='https://www.vfl-ramsdorf.de/breitensport/kurs/56/buchen'&&!$c['ongoing']&&!$c['detailsMissing'],'booking link');
$ongoing=Vfl\Breitensport::merge($cards[1],null);
check($ongoing['ongoing']&&$ongoing['endDate']===null&&$ongoing['detailsMissing']&&$ongoing['seatsLeft']===null,'ongoing course without details stays listed');
check(Vfl\Breitensport::merge($cards[2],null)['endDate']==='2026-12-22','end date with spaced range');
check(Vfl\Breitensport::endDate(['31.2.26'])===null,'invalid date rejected');
$failed=false;try{Vfl\Breitensport::parseCourse('<h1>Leer</h1>',1);}catch(Throwable){$failed=true;}check($failed,'empty course page rejected');
$data=Vfl\Breitensport::offers();$ids=[];
foreach($data['offers'] as $o){
 check(!isset($ids[$o['id']])&&preg_match('/^[a-z0-9-]+$/',$o['id'])===1,'unique offer id '.$o['id']);$ids[$o['id']]=true;
 check(str_starts_with($o['url'],'https://www.vfl-ramsdorf.de/'),'club link '.$o['id']);
 check(array_diff($o['groups'],['kinder','erwachsene','senioren'])===[]&&$o['groups'],'groups '.$o['id']);
 foreach($o['sessions'] as $s){
  check(is_int($s['day'])&&$s['day']>=1&&$s['day']<=6,'weekday '.$o['id']);
  check(!isset($s['dates'])||(is_array($s['dates'])&&$s['dates']),'dates list '.$o['id']);
  check(preg_match('/^\d\d:\d\d$/',$s['start'])===1&&preg_match('/^\d\d:\d\d$/',$s['end'])===1&&$s['start']<$s['end'],'time range '.$o['id']);
  if(isset($s['leader']))check(in_array($s['leader'],array_column($o['leaders'],'name'),true),'leader listed '.$o['id']);
 }
}
check(count($data['offers'])===18&&count($data['contacts'])===3,'all website offers and contacts');
foreach($data['offers'] as $o){foreach($o['sessions'] as $s)foreach($s['dates']??[] as $d){check(preg_match('/^\d{4}-\d\d-\d\d$/',$d)===1&&(int)date('w',strtotime($d))%7===$s['day']%7,'dated session on its weekday '.$o['id'].' '.$d);}
 foreach($o['events']??[] as $e)check(preg_match('/^\d{4}-\d\d-\d\d$/',$e['date'])===1&&preg_match('/^\d\d:\d\d$/',$e['start'])===1&&$e['title']!=='','event '.$o['id']);}
echo "PASS: PHP Breitensport course list, details, prices, seats, dates, link/image restrictions and maintained offers.\n";
