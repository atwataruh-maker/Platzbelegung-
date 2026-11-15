import assert from 'node:assert/strict';
import fs from 'node:fs';
import {build} from 'esbuild';
const b=await build({stdin:{contents:"export * from './lib/breitensport';",resolveDir:process.cwd(),loader:'ts'},bundle:true,write:false,format:'esm'});
const L=await import('data:text/javascript;base64,'+Buffer.from(b.outputFiles[0].text).toString('base64'));
const base=JSON.parse(fs.readFileSync('server/breitensport.json','utf8'));
const course=(title,extra={})=>({id:1,title,url:'https://www.vfl-ramsdorf.de/breitensport/kurs/1',image:null,teaser:[],leader:null,startDate:'2026-11-05',endDate:'2026-12-17',ongoing:false,units:7,venue:null,priceMember:28,priceGuest:35,seatsLeft:5,bookingUrl:null,...extra});
const feed={retrievedAt:'2026-11-18T14:00:00Z',source:'',courses:[course('Line Dance - Level I',{seatsLeft:2}),course('Line Dance - Level II'),course('Line Dance - Level III'),
 course('Dancing Fit I',{startDate:'2026-10-27',endDate:'2026-12-22',seatsLeft:0}),course('Dancing Fit II',{startDate:'2026-10-27',endDate:'2026-12-22'}),course('Fit bis ins Hohe Alter',{startDate:'2026-04-15',endDate:null,ongoing:true})]};
const data={...base,courses:feed};
const titles=(date,group)=>L.sessionsFor(data,date,group).map(e=>e.offer.title+' '+e.session.start);
// Week navigation: Monday to Saturday; Sunday shows the coming week.
assert.deepEqual(L.weekDays('2026-11-18'),['2026-11-16','2026-11-17','2026-11-18','2026-11-19','2026-11-20','2026-11-21']);
assert.deepEqual(L.weekDays('2026-11-15'),['2026-11-16','2026-11-17','2026-11-18','2026-11-19','2026-11-20','2026-11-21']);
assert.equal(L.defaultDay('2026-11-15'),'2026-11-16');assert.equal(L.defaultDay('2026-11-21'),'2026-11-21');
// Wednesday in November: winter venue for athletics, courses inside their period, sorted by time.
assert.deepEqual(titles('2026-11-18'),['Fit bis ins hohe Alter 14:30','Leichtathletik 15:30','Eltern-Kind-Turnen 16:00','Leichtathletik 16:40','Vorschulkinder Turnen 17:15','Schulkindturnen 18:15','Aerobic 19:30']);
assert.equal(L.sessionsFor(data,'2026-11-18').find(e=>e.offer.id==='leichtathletik').session.venue,'Alte Molkerei · Kursraum');
assert.equal(L.sessionsFor(data,'2027-04-14').find(e=>e.offer.id==='leichtathletik').session.venue,'Wulfkampstadion · Laufbahn');
assert.deepEqual(titles('2026-11-18','senioren'),['Fit bis ins hohe Alter 14:30','Aerobic 19:30']);
assert.deepEqual(titles('2026-11-21'),['Eltern-Kind-Fußball 09:00','Eltern-Kind-Fußball 10:00','Sportabzeichen 10:00']);
// Courses: shown within their period; ended or unlisted courses disappear once live data exists.
assert.ok(titles('2026-11-19').includes('Line Dance 16:30'));
assert.ok(!titles('2026-12-24').includes('Line Dance 16:30'));
assert.ok(!L.sessionsFor({...data,courses:{...feed,courses:feed.courses.filter(c=>!c.title.startsWith('Line'))}},'2026-11-19').some(e=>e.offer.id==='line-dance'));
assert.ok(L.sessionsFor({...data,courses:null},'2026-12-24').some(e=>e.offer.id==='line-dance'),'without live data the maintained plan stays visible');
assert.equal(L.sessionsFor(data,'2026-11-19').find(e=>e.offer.id==='line-dance').course.seatsLeft,2);
assert.ok(L.courseFor(feed,'Fit bis ins Hohe Alter')&&L.courseRuns(feed,'Fit bis ins Hohe Alter','2027-06-02'),'ongoing course');
assert.deepEqual(L.activeCourses(feed,'2026-12-20').map(c=>c.title),['Dancing Fit I','Dancing Fit II','Fit bis ins Hohe Alter']);
// Timing on the current day only.
const s={start:'16:00',end:'17:00'};
assert.equal(L.timing(s,'2026-11-18','2026-11-18',15*60+59),'upcoming');assert.equal(L.timing(s,'2026-11-18','2026-11-18',16*60),'now');
assert.equal(L.timing(s,'2026-11-18','2026-11-18',17*60),'done');assert.equal(L.timing(s,'2026-11-17','2026-11-18',0),'done');assert.equal(L.timing(s,'2026-11-19','2026-11-18',23*60),'upcoming');
assert.equal(L.berlinMinutes(new Date('2026-11-18T15:30:00Z')),16*60+30);assert.equal(L.berlinDate(new Date('2026-11-18T23:30:00Z')),'2026-11-19');
// Seats, money, links.
assert.deepEqual(L.seats(course('x',{seatsLeft:0})),{label:'Ausgebucht',tone:'full'});
assert.equal(L.seats(course('x',{seatsLeft:1})).label,'Nur noch 1 Platz');assert.equal(L.seats(course('x',{seatsLeft:3})).tone,'few');
assert.equal(L.seats(course('x',{seatsLeft:4})).tone,'ok');assert.equal(L.seats(course('x',{seatsLeft:null})).tone,'unknown');
assert.equal(L.euro(28).replace(/\s/g,' '),'28 €');assert.equal(L.euro(35.5).replace(/\s/g,' '),'35,50 €');
assert.equal(L.telHref('+49 160 7538085'),'tel:+491607538085');
assert.equal(L.mailHref('a@b.de'),'mailto:a@b.de');assert.equal(L.mailHref('x"><script>@b.de'),undefined);
assert.equal(L.safeClubLink('javascript:alert(1)'),'https://www.vfl-ramsdorf.de/breitensport');
assert.equal(L.safeClubLink('https://www.vfl-ramsdorf.de.evil.test/x'),'https://www.vfl-ramsdorf.de/breitensport');
assert.equal(L.initials('Großmeister Sin Kil Yoon'),'SK');
// Calendar export: one weekly event per session, course end and athletics months respected.
const dance=L.ics(base.offers.find(o=>o.id==='line-dance'),'2026-11-18',feed);
assert.equal(dance.match(/BEGIN:VEVENT/g).length,3);
assert.match(dance,/DTSTART;TZID=Europe\/Berlin:20261119T163000/);assert.match(dance,/RRULE:FREQ=WEEKLY;BYDAY=TH;UNTIL=20261217T235959Z/);
const athletics=L.ics(base.offers.find(o=>o.id==='leichtathletik'),'2026-11-18',null);
assert.match(athletics,/DTSTART;TZID=Europe\/Berlin:20261118T153000/);assert.match(athletics,/DTSTART;TZID=Europe\/Berlin:20270407T153000/);
assert.match(athletics,/BYMONTH=4,5,6,7,8,9,10/);assert.ok(athletics.includes('\r\n'));
assert.match(L.ics(base.offers.find(o=>o.id==='trampolin'),'2026-11-18',null),/SUMMARY:Trampolin \(Anfänger ab 3 J\.\) · VfL Ramsdorf/);
console.log('PASS: Breitensport week, filters, seasonal venues, course periods, timing, seats, safe links and calendar export.');
