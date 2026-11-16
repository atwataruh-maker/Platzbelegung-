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
// Times as written on the website.
$t=fn($x,$d=null)=>Vfl\Breitensport::parseTime($x,$d);
check($t('Mittwochs 16:00 – 17:00 Uhr ')===['day'=>3,'start'=>'16:00','end'=>'17:00'],'heading time');
check($t('Samstags: 9:00 - 10:00 Uhr')===['day'=>6,'start'=>'09:00','end'=>'10:00'],'colon after day');
check($t('Donnerstags 17:15 Uhr - 17:45 Uhr')===['day'=>4,'start'=>'17:15','end'=>'17:45'],'Uhr twice');
check($t('Jeweils dienstags 17-18 Uhr')===['day'=>2,'start'=>'17:00','end'=>'18:00'],'full hours');
check($t('jeweils donnerstags, 16.30h.')===['day'=>4,'start'=>'16:30','end'=>null],'start only');
check($t('Immer donnerstags ab 19.00 Uhr')===['day'=>4,'start'=>'19:00','end'=>null],'ab');
check($t('Ab 5.11.-17.12.26 jeweils donnerstags')===null&&$t('Fortlaufender Kurs, immer mittwochs nachmittags')===null,'dates are no times');
check($t('Leichtathletik (4-7 J.)',3)===null&&$t('16-17:30 Uhr',1)===['day'=>1,'start'=>'16:00','end'=>'17:30'],'ages are no times');
$detail='<div class="row" id="block-1"><div class="col"><h1>Trampolin</h1><h5>&Uuml;bungsleiterin</h5><p>Mandy Lahme<br>Tel: 0151</p><h5>Zeit</h5><p>Montags 17:30 &ndash; 19:00 Uhr&nbsp;</p><h5>Ort</h5><p>Sporthalle - Paulusstra&szlig;e</p><h5>Beschreibung</h5><p>Anf&auml;nger</p>
<h5>&Uuml;bungsleiterin</h5><p>Laura Bach</p><h5>Zeit</h5><p>Montags 16:00 &ndash; 17:30 Uhr</p><h5>Ort</h5><p>Sporthalle - Paulusstra&szlig;e</p></div></div>
<div class="row" id="block-2"><div class="col"><h1>Dancing Fit</h1><h5>&Uuml;bungsleiterin</h5><p>Andrea Fortmann<br>E-Mail: a@b.de</p><h5>Zeit</h5><p>Dienstags 17:00 &ndash; 18:00 Uhr</p><h5>Ort</h5><p>Alte Molkerei - Kursraum</p><h5>Beschreibung</h5><p>Gruppe I</p><h5>Zeit</h5><p>Dienstags 18:15 &ndash; 19:15 Uhr</p><h5>Ort</h5><p>Alte Molkerei - Kursraum</p></div></div>
<div class="row" id="block-3"><div class="col"><p>Kursleiterin: Sabine Scheer<br>Zeit: Donnerstags 17:00 &ndash; 18:00 Uhr<br>Ort: Sporthalle - In der Worthe<br>Beschreibung: Anf&auml;nger</p></div></div>
<div class="row" id="block-4"><div class="col"><h1>Taekwondo</h1><h5>Gro&szlig;meister</h5><p>Sin Kil Yoon</p><h5>Zeit</h5><p>Donnerstags 17:30 &ndash; 19:00 Uhr</p></div></div>
<div class="row" id="block-5"><h5>&Uuml;bungsleiter</h5><p>Ludger Ro&szlig;kamp,<br>+49 2863, x@y.de</p><h5>Zeit</h5><p>Montags 15:00 &ndash; 16:00 Uhr<br>Samstags 10:00 &ndash; 11:30 Uhr</p><h5>Ort</h5><p>Sportplatz &bdquo;Wulfkampstadion&ldquo;</p><p>Zeitraum: ganzj&auml;hrig</p></div>
<div class="row" id="block-6"><section><div class="ekf-card"><h3>2- bis 3-J&auml;hrige</h3><div class="ekf-label">Trainer</div><img alt="Trainer X"><div class="ekf-name">Benedikt Sicking</div><div class="ekf-label">Trainingszeit</div><div>Samstags: 9:00 - 10:00 Uhr<br>Sporthalle Paulusstra&szlig;e</div></div></section></div>';
$e=Vfl\Breitensport::parseDetails($detail,'test');
$row=fn($x)=>[$x['leader'],$x['day'],$x['start'],$x['end'],$x['venue']];
check(array_map($row,$e)===[
 ['Mandy Lahme',1,'17:30','19:00','Sporthalle - Paulusstraße'],['Laura Bach',1,'16:00','17:30','Sporthalle - Paulusstraße'],
 ['Andrea Fortmann',2,'17:00','18:00','Alte Molkerei - Kursraum'],['Andrea Fortmann',2,'18:15','19:15','Alte Molkerei - Kursraum'],
 ['Sabine Scheer',4,'17:00','18:00','Sporthalle - In der Worthe'],['Großmeister Sin Kil Yoon',4,'17:30','19:00',null],
 ['Ludger Roßkamp',1,'15:00','16:00','Sportplatz „Wulfkampstadion“'],['Ludger Roßkamp',6,'10:00','11:30','Sportplatz „Wulfkampstadion“'],
 ['Benedikt Sicking',6,'09:00','10:00','Sporthalle Paulusstraße']],'detail blocks: headings, inline labels, groups, leaders, cards');
check($e[0]['audience']==='Anfänger'&&$e[0]['title']==='Trampolin','audience and title');
$grid='<div class="card angebote-card"><h4>Alte Molkerei - Kursraum</h4><table><thead><tr><th>Montag</th><th>Dienstag</th><th>Mittwoch</th></tr></thead><tbody><tr><td></td><td></td><td>November-M&auml;rz<br>Leichtathletik (4-7 J.)<br>15:30 - 16:30 Uhr</td></tr></tbody></table></div>';
check(array_map(fn($x)=>[$x['day'],$x['start'],$x['end'],$x['venue']],Vfl\Breitensport::parseGrid($grid))===[[3,'15:30','16:30','Alte Molkerei - Kursraum']],'overview grid');
// Reconcile: website wins, previous values are kept.
$offer=fn($id,$url,$sessions,$leaders=[])=>['id'=>$id,'title'=>ucfirst($id),'url'=>'https://www.vfl-ramsdorf.de/breitensport/'.$url,'leaders'=>$leaders,'sessions'=>$sessions];
$base=['offers'=>[
 $offer('aerobic','seniorensport',[['day'=>3,'start'=>'19:30','end'=>'20:30','venue'=>'Alte Molkerei · Kursraum','leader'=>'Marion Schmidt']],[['name'=>'Marion Schmidt']]),
 $offer('badminton','mannschaftssport',[['day'=>4,'start'=>'20:00','end'=>'22:00','venue'=>'Sporthalle In der Worthe','leader'=>'Werner Knuf']],[['name'=>'Werner Knuf','phone'=>'+49']]),
 $offer('volleyball','mannschaftssport',[['day'=>1,'start'=>'20:00','end'=>'22:00','venue'=>'Sporthalle Paulusstraße','leader'=>'Markus Schöttler']]),
 $offer('trampolin','kindersport',[['day'=>1,'start'=>'16:00','end'=>'17:30','venue'=>'Sporthalle Paulusstraße','leader'=>'Laura Bach']]),
 $offer('line dance','kurse',[['day'=>4,'start'=>'16:30','end'=>'17:30','venue'=>'Sporthalle In der Worthe','leader'=>'Sabine Scheer','course'=>'Line Dance - Level I']]),
 $offer('leichtathletik','',[['day'=>3,'start'=>'15:30','end'=>'16:30','venue'=>'Wulfkampstadion · Laufbahn','months'=>[4]],['day'=>3,'start'=>'15:30','end'=>'16:30','venue'=>'Alte Molkerei · Kursraum','months'=>[11]]]),
],'courses'=>['courses'=>[['title'=>'Line Dance - Level I','teaser'=>['5.11.26-17.12.26','jeweils donnerstags, 17.00h.']]]]];
$live=['retrievedAt'=>'2026-11-16T10:00:00+00:00','pages'=>['seniorensport'=>true,'mannschaftssport'=>true,'kindersport'=>false,'kurse'=>true,'breitensport'=>true],'entries'=>[
 ['page'=>'seniorensport','title'=>'Aerobic','leader'=>'Marion Schmidt','venue'=>'Alte Molkerei - Kursraum','audience'=>null,'day'=>3,'start'=>'19:00','end'=>'20:15'],
 ['page'=>'mannschaftssport','title'=>'Badminton Mixed','leader'=>'Hans Neu','venue'=>'Sporthalle - In der Worthe','audience'=>null,'day'=>4,'start'=>'20:00','end'=>'22:00'],
 ['page'=>'mannschaftssport','title'=>'Neu: Yoga','leader'=>'Eva','venue'=>'Halle','audience'=>null,'day'=>5,'start'=>'18:00','end'=>'19:00'],
 ['page'=>'breitensport','title'=>'November-März Leichtathletik (4-7 J.)','leader'=>null,'venue'=>'Alte Molkerei - Kursraum','audience'=>null,'day'=>3,'start'=>'15:45','end'=>'16:45'],
 ['page'=>'breitensport','title'=>'ab April Leichtathletik (4-7 J.)','leader'=>null,'venue'=>'Wulfkampstadion','audience'=>null,'day'=>3,'start'=>'15:30','end'=>'16:30'],
]];
$r=Vfl\Breitensport::reconcile($base,$live);$o=array_column($r['offers'],null,'id');
$a=$o['aerobic']['sessions'][0];check($a['start']==='19:00'&&$a['end']==='20:15'&&$a['live']['state']==='updated'&&$a['live']['planned']['start']==='19:30'&&$a['venue']==='Alte Molkerei · Kursraum','changed time, equivalent venue kept');
$b=$o['badminton']['sessions'][0];check($b['leader']==='Hans Neu'&&$b['live']['state']==='updated'&&$b['live']['planned']['leader']==='Werner Knuf'&&array_column($o['badminton']['leaders'],'name')===['Hans Neu'],'new leader replaces old contact');
check($o['volleyball']['sessions'][0]['live']['state']==='missing','session gone from the website');
check($o['trampolin']['sessions'][0]['live']['state']==='unchecked'&&$o['trampolin']['sessions'][0]['start']==='16:00','unreadable page keeps maintained times');
$l=$o['line dance']['sessions'][0];check($l['start']==='17:00'&&$l['end']==='18:00'&&$l['live']['source']==='kurs'&&$l['live']['state']==='updated','course text wins, duration kept');
check($o['leichtathletik']['sessions'][0]['live']['state']==='confirmed'&&$o['leichtathletik']['sessions'][0]['venue']==='Wulfkampstadion · Laufbahn','grid: summer venue');
check($o['leichtathletik']['sessions'][1]['start']==='15:45'&&$o['leichtathletik']['sessions'][1]['live']['state']==='updated','grid: winter time changed');
check(array_column($r['live']['unmatched'],'title')===['Neu: Yoga'],'new website entries reported');
$none=Vfl\Breitensport::reconcile($base,null);check($none['live']===null&&$none['offers'][0]['sessions'][0]['live']['state']==='unchecked'&&$none['offers'][0]['sessions'][0]['start']==='19:30','no live data');
echo "PASS: PHP Breitensport course list, website times, reconcile, details, prices, seats, dates, link/image restrictions and maintained offers.\n";
