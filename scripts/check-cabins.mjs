import assert from 'node:assert/strict';
import {build} from 'esbuild';
const bundle=await build({stdin:{contents:"export * from './lib/cabin-allocation';export * from './lib/pitch-allocation';export * from './lib/training-plan';",resolveDir:process.cwd()},bundle:true,write:false,format:'esm',platform:'node'});
const {allocate,allocateCabins,FRONT,BACK,MAIN,overlaps,TRAINING}=await import('data:text/javascript;base64,'+Buffer.from(bundle.outputFiles[0].text).toString('base64'));
const date='2026-10-19';
const training=(team,fields=FRONT,start=990,end=1080)=>({id:team,day:1,team,small:/^[EFG]/.test(team),season:'all',segments:[{start,end,fields}]});
const game=(id,category='Herren',extra={})=>({id,category,date,time:'17:00',home:'VfL Ramsdorf',away:'Gast '+id,competition:'Liga',location:'Heim',status:'',url:'https://www.fussball.de/spiel/'+id,...extra});
const schedule=(matches=[])=>({from:date,to:date,matches,retrievedAt:'2026-10-19T10:00:00Z',stale:false,provisional:false,omittedRows:0});
function plan(teams=[],matches=[],extra={}){const s={...schedule(matches),...extra};return allocateCabins(allocate(date,s,true,teams),s);}
function noRoomClashes(p){for(let i=0;i<p.bookings.length;i++)for(let j=i+1;j<p.bookings.length;j++){
 const a=p.bookings[i],b=p.bookings[j];
 assert.ok(!a.room||a.room!==b.room||a.start>=b.end||b.start>=a.end||(a.shareEligible&&b.shareEligible&&a.gender!=='unknown'&&a.gender===b.gender&&a.guest===b.guest&&!a.fixed&&!b.fixed),`${a.team} / ${b.team} overlap in ${a.room}`);
}}
let p=plan([training('1. Herren'),training('2. Herren',BACK)]);
assert.equal(p.bookings.find(b=>b.team==='1. Herren').room,'home1');
assert.equal(p.bookings.find(b=>b.team==='2. Herren').room,'home2');
assert.equal(p.bookings[0].start,975);assert.equal(p.bookings[0].end,1095);noRoomClashes(p);
for(const [name,room] of [['VfL Ramsdorf','home1'],['VfL Ramsdorf I','home1'],['VfL Ramsdorf 1','home1'],['VfL Ramsdorf II','home2'],['VfL Ramsdorf 2','home2']]){
 p=plan([], [game('senior','Herren',{home:name})]);
 assert.equal(p.bookings.find(b=>!b.guest).room,room);assert.equal(p.bookings.find(b=>b.guest).room,'away1');noRoomClashes(p);
}
// Category prevents the B-youth second team being mistaken for the second men's team.
p=plan([], [game('youth','B-Junioren',{home:'VfL Ramsdorf II'})]);assert.equal(p.bookings.find(b=>!b.guest).fixed,null);
p=plan([], [game('women','Frauen')]);assert.ok(p.bookings.every(b=>b.gender==='female'));
// Women use only a free home cabin, exclusively, for both training and matches.
for(const viaMatch of [false,true])for(const occupied of [[],['1. Herren'],['2. Herren'],['1. Herren','2. Herren']]){
 const women=viaMatch?[]:[training('VfL Ramsdorf Damen',['r1'])];
 p=plan([...occupied.map(n=>training(n)),...women,training('C-Juniorinnen',['r2']),training('E1-Juniorinnen',['r3'])],viaMatch?[game('women','Frauen')]:[]);
 const w=p.bookings.find(b=>!b.guest&&(b.team==='VfL Ramsdorf Damen'||b.category==='Frauen'));
 assert.equal(w.shareEligible,false);
 if(occupied.length===2){assert.equal(w.room,null);assert.match(w.reason,/weder die 1. noch die 2. Heimkabine/);}
 else{assert.ok(['home1','home2'].includes(w.room));assert.equal(w.sharingWith.length,0);if(occupied[0]==='1. Herren')assert.equal(w.room,'home2');if(occupied[0]==='2. Herren')assert.equal(w.room,'home1');}
 if(viaMatch)assert.equal(p.bookings.find(b=>b.guest).room,'away1');
 noRoomClashes(p);
}
// A club derby preserves the fixed cabin even for the team listed as away.
p=plan([], [game('derby','Herren',{home:'VfL Ramsdorf III',away:'VfL Ramsdorf II',location:'Vereinsduell'})]);assert.equal(p.bookings.find(b=>b.team==='2. Herren').room,'home2');
// Two visitors use the two designated visitor cabins; genders never share one at once.
p=plan([], [game('men'),game('women','Frauen')]);
assert.deepEqual(p.bookings.filter(b=>b.guest).map(b=>b.room).sort(),['away1','shared']);noRoomClashes(p);
// Fixed team training takes priority even when other teams have a home fixture.
p=plan([training('1. Herren'),training('2. Herren',BACK)],[game('youth','B-Junioren')]);
assert.equal(p.bookings.find(b=>b.team==='1. Herren').room,'home1');assert.equal(p.bookings.find(b=>b.team==='2. Herren').room,'home2');noRoomClashes(p);
// One team's match overlapping its training is one continuous cabin reservation.
p=plan([training('1. Herren')],[game('same-team')]);
const first=p.bookings.filter(b=>b.team==='1. Herren');assert.equal(first.length,1);assert.equal(first[0].room,'home1');assert.deepEqual(first[0].activities,['Training','Heimspiel']);noRoomClashes(p);
p=plan([training('C-Junioren')],[game('same-youth','C-Junioren')]);assert.equal(p.bookings.filter(b=>!b.guest).length,1);assert.equal(p.bookings.find(b=>!b.guest).activities.length,2);noRoomClashes(p);
p=plan([training('D1-Junioren'),training('D2-Junioren',BACK)],[game('second-youth','D-Junioren',{home:'VfL Ramsdorf II'})]);assert.equal(p.bookings.filter(b=>!b.guest).length,2);assert.equal(p.bookings.find(b=>b.team==='D2-Junioren').activities.length,2);assert.equal(p.bookings.find(b=>b.team==='D1-Junioren').activities.length,1);noRoomClashes(p);
p=plan([training('G-Junioren / G-F-Juniorinnen')]);
assert.equal(p.bookings.length,2);assert.deepEqual(p.bookings.map(b=>b.gender).sort(),['female','male']);assert.notEqual(p.bookings[0].room,p.bookings[1].room);noRoomClashes(p);
p=plan([training('Nordvelen')]);assert.equal(p.bookings[0].room,'away1');assert.equal(p.bookings[0].gender,'unknown');assert.equal(p.bookings[0].shareEligible,false);assert.equal(p.conflicts.length,0);
p=plan([training('Unbekannter Verein')]);assert.equal(p.bookings[0].room,null);assert.match(p.conflicts[0],/Geschlechtszuordnung/);
// Home youth teams can use all four cabins without guests, with genders separate.
p=plan([training('1. Herren'),training('2. Herren',BACK),training('A-Junioren',['r1']),training('C-Juniorinnen',['r2'])]);
assert.equal(p.bookings.filter(b=>!b.room).length,0);assert.ok(p.bookings.some(b=>b.room==='away1'&&!b.guest));noRoomClashes(p);
const fixedTeams=[training('1. Herren'),training('2. Herren',BACK)];
// With three rooms occupied, all same-gender youth age groups can share the fourth.
for(const names of [['D1-Junioren','E1-Junioren'],['D1-Juniorinnen','G/F-Juniorinnen'],['F1/F2-Junioren','G-Junioren'],['A-Junioren','B-Junioren'],['D1-Junioren','C-Junioren'],['D1-Junioren','C/D-Junioren'],['C-Juniorinnen','E1/E2-Juniorinnen']]){
 p=plan([...fixedTeams,...names.map(n=>training(n,['r1']))],[game('visitor')]);
 const youth=p.bookings.filter(b=>names.includes(b.team));assert.equal(youth.length,2);assert.equal(youth[0].room,'shared');assert.equal(youth[1].room,'shared');assert.deepEqual(youth[0].sharingWith,[youth[1].team]);assert.deepEqual(youth[1].sharingWith,[youth[0].team]);assert.equal(p.conflicts.length,0);noRoomClashes(p);
}
// No sharing across genders or with adults.
for(const names of [['D1-Junioren','D1-Juniorinnen'],['D1-Junioren','3. Herren']]){
 p=plan([...fixedTeams,...names.map(n=>training(n,['r1']))],[game('visitor')]);
 assert.equal(p.bookings.filter(b=>!b.room).length,1);noRoomClashes(p);
}
// Guests retain priority; a home youth side never shares an occupied guest cabin.
p=plan(fixedTeams,[game('visitor'),game('young','D-Junioren')]);
assert.ok(p.bookings.filter(b=>b.guest).every(b=>b.room));assert.equal(p.bookings.find(b=>b.category==='D-Junioren'&&!b.guest).room,null);noRoomClashes(p);
// Sharing also applies to visitors of the same gender through A youth.
p=plan([],['one','two','three'].map(id=>game(id,'A-Junioren')));
const guests=p.bookings.filter(b=>b.guest);assert.equal(guests.length,3);assert.ok(guests.every(b=>b.room));assert.ok(guests.some(b=>b.sharingWith.length));noRoomClashes(p);
// Guest reservation and changing buffers must end before a home team can take over.
for(const start of [1170,1169]){
 p=plan([training('1. Herren',FRONT,1170,1260),training('2. Herren',BACK,1170,1260),training('3. Herren',['r1'],start,1260),training('Alte Herren',['r2'],start,1260)],[game('visitor')]);
 const late=p.bookings.find(b=>b.team==='Alte Herren');
 if(start===1170){assert.ok(late.room);assert.equal(p.conflicts.length,0);}else{assert.equal(p.bookings.filter(b=>!b.room).length,1);assert.ok(late.room!=='away1');}
 noRoomClashes(p);
}
// Rooms are reusable for another gender once the complete changing interval ends.
p=plan([training('C-Junioren',FRONT,900,960),training('C-Juniorinnen',BACK,990,1050)]);
assert.equal(p.bookings[0].room,p.bookings[1].room);noRoomClashes(p);
for(const extra of [{location:'Auswärts'},{status:'Ausfall'},{status:'Absetzung'},{status:'Verlegt: 20.10.2026 17:00'}])assert.equal(plan([],[game('ignore','Herren',extra)]).bookings.length,0);
assert.equal(plan([training('1. Herren')],[],{stale:true}).confirmed,false);
const emptyAllocation=allocate(date,null,true,[training('1. Herren')]);assert.equal(allocateCabins(emptyAllocation,null).confirmed,false);
// Full-week data, including the mixed Friday group and exclusive Nordvelen entry.
for(let day=19;day<=25;day++){
 const d=`2026-10-${day}`,s={...schedule(),from:d,to:d};const a=allocate(d,s,true);p=allocateCabins(a,s);noRoomClashes(p);
 const sessions=TRAINING.filter(t=>t.day===new Date(d+'T12:00Z').getUTCDay());
 assert.equal(p.bookings.length,sessions.length+(day===23?1:0));
}

// Regressions for the reported dates, using the October 17 source check:
// Oct 15 C-youth fixture at 18:00; Oct 16 fixture was moved to November 2.
for(const d of ['2026-10-15','2026-10-16']){
 const s={...schedule(d.endsWith('15')?[game('031R0M5T7K000000VS5489BUVVE0O1R6','C-Junioren',{date:d,time:'18:00',away:'RSV Borken'})]:[]),from:d,to:d};
 p=allocateCabins(allocate(d,s,true),s);noRoomClashes(p);
 assert.ok(p.bookings.filter(b=>b.gender!=='unknown').every(b=>b.room),d+' all known teams must fit');
 if(d.endsWith('15')){
  assert.equal(p.bookings.filter(b=>!b.room).length,0);assert.notEqual(p.bookings.find(b=>b.team==='Nordvelen').room,'away1');assert.deepEqual(p.bookings.find(b=>b.team==='Nordvelen').sharingWith,[]);
  assert.equal(p.bookings.find(b=>b.team==='1. Herren').room,'home1');
  assert.equal(p.bookings.find(b=>b.team==='RSV Borken').room,'away1');
  assert.ok(['A-Junioren','B-Junioren'].every(t=>p.bookings.find(b=>b.team===t).sharingWith.length));
 }else{
  assert.equal(p.conflicts.length,0);assert.equal(p.bookings.find(b=>b.team==='VfL Ramsdorf Damen').room,'home1');
  assert.equal(p.bookings.find(b=>b.team==='2. Herren').room,'home2');
  assert.ok(p.bookings.find(b=>b.team==='C-Juniorinnen').sharingWith.includes('E1/E2-Juniorinnen'));
 }
}

// Main-pitch fallback: exactly two half-pitch sessions, games block both halves.
const busyGrass=[training('fixed-r1',['r1']),training('fixed-r2',['r2']),training('fixed-r3',['r3']),training('fixed-corner',['corner'])];
let a=allocate(date,schedule([game('turf','B-Junioren')]),true,[training('1. Herren'),training('B-Junioren',BACK),...busyGrass]);
assert.equal(a.conflicts.length,0);
assert.deepEqual(a.bookings.filter(b=>b.moved).map(b=>b.segments[0].fields).sort(),[['main1'],['main2']]);
for(let i=0;i<a.bookings.length;i++)for(let j=i+1;j<a.bookings.length;j++)assert.ok(!a.bookings[i].segments.some(s=>a.bookings[j].segments.some(t=>overlaps(s,t))));
a=allocate(date,schedule([game('turf','B-Junioren'),game('main')]),true,[training('1. Herren'),training('B-Junioren',BACK),...busyGrass]);
assert.equal(a.bookings.filter(b=>b.kind==='training'&&!b.segments.length).length,2);assert.deepEqual(a.bookings.find(b=>b.id==='match-main').segments[0].fields,MAIN);
// Other adult teams cannot take the stadium; girls are eligible just like boys.
for(const team of ['2. Herren','3. Herren','Nordvelen','VfL Ramsdorf Damen']){
 a=allocate(date,schedule([game('turf','B-Junioren')]),true,[training(team),...busyGrass]);assert.equal(a.bookings.find(b=>b.id===team).segments.length,0);
}
for(const team of ['C-Juniorinnen','G/F-Juniorinnen','A-Junioren']){
 a=allocate(date,schedule([game('turf','B-Junioren')]),true,[training(team),...busyGrass]);assert.equal(a.bookings.find(b=>b.id===team).segments[0].fields[0],'main1');
}
a=allocate(date,schedule([game('turf','B-Junioren')]),true,[training('1. Herren'),training('B-Junioren',BACK)]);
assert.ok(a.bookings.filter(b=>b.kind==='training').every(b=>b.segments.every(s=>!s.fields.some(f=>MAIN.includes(f)))));
console.log('PASS: fixed first/second team cabins, guests/overflow, gender separation, mixed groups, unknown gender, capacity conflicts, time buffers, merged reservations, stale/cancelled/away/moved fixtures; main-pitch halves, eligibility, final fallback, match blocking.');

// Recurring room swap requested by trainers from Wednesday 28 October onwards.
const weeklyRooms={'C-Juniorinnen':'home1','D1-Juniorinnen':'home1','D1-Junioren':'shared','E2-Junioren':'shared'};
for(const day of ['2026-10-28','2026-11-04','2026-11-11']){
 const s={...schedule(),from:day,to:day};
 const a=allocate(day,s,true);const c=allocateCabins(a,s);noRoomClashes(c);
 for(const [team,room] of Object.entries(weeklyRooms))assert.equal(c.bookings.find(b=>b.team===team)?.room,room,`${day}: ${team}`);
 assert.equal(c.bookings.find(b=>b.team==='2. Herren').room,'home2');
 assert.equal(c.bookings.find(b=>b.team==='Alte Herren').room,'home1','Alte Herren follow the girls, without overlapping E2');
 assert.equal(c.conflicts.length,0);assert.equal(c.notes,undefined,'Regular Wednesday must not display a rules banner');
 if(day==='2026-10-28')console.log('Wednesday assignment:',c.bookings.map(b=>`${b.team}: ${b.room} ${b.start}-${b.end}`).join('; '));
 // Fixed first men's room remains protected if they add an overlapping session.
 const withFirst={...a,bookings:[...a.bookings,{id:'extra-first',kind:'training',title:'1. Herren',segments:[{start:1050,end:1140,fields:MAIN}]}]};
 const constrained=allocateCabins(withFirst,s);noRoomClashes(constrained);
 assert.equal(constrained.bookings.find(b=>b.team==='1. Herren').room,'home1');
 for(const team of ['C-Juniorinnen','D1-Juniorinnen'])assert.notEqual(constrained.bookings.find(b=>b.team===team).room,'home1');
 assert.ok(constrained.notes.some(n=>n.includes('Wegen anderer Belegungen')));
 // Adult women still have an exclusive home cabin.
 const withWomen={...a,bookings:[...a.bookings,{id:'extra-women',kind:'training',title:'VfL Ramsdorf Damen',segments:[{start:1050,end:1140,fields:MAIN}]}]};
 const womenPlan=allocateCabins(withWomen,s);noRoomClashes(womenPlan);
 const w=womenPlan.bookings.find(b=>b.team==='VfL Ramsdorf Damen');assert.ok(['home1','home2'].includes(w.room));assert.equal(w.sharingWith.length,0);
}
for(const day of ['2026-10-21','2026-10-27','2026-10-29']){
 const s={...schedule(),from:day,to:day};assert.equal(allocateCabins(allocate(day,s,true),s).notes,undefined,'No Wednesday override before start or on other weekdays');
}
console.log('PASS: recurring Wednesday room swap, subsequent Alte Herren booking, fixed senior cabins, women and fallback notices.');

// Nordvelen shares the grounds, always an exclusive room and no guessed gender.
for(const day of ['2026-10-29','2026-11-05']){
 const s={...schedule(),from:day,to:day};const a=allocate(day,s,true);const c=allocateCabins(a,s);noRoomClashes(c);
 const nord=c.bookings.find(b=>b.team==='Nordvelen');assert.equal(nord.room,'away1');assert.equal(nord.start,1125);assert.equal(nord.end,1245);assert.equal(nord.gender,'unknown');assert.equal(nord.shareEligible,false);assert.deepEqual(nord.sharingWith,[]);assert.equal(c.conflicts.length,0);assert.equal(c.notes,undefined);
 // A real visiting side takes the away cabin; Nordvelen must move, not share.
 const match=game('nord-clash','Herren',{date:day,time:'19:00'});const busy={...s,matches:[match]};const moved=allocateCabins(allocate(day,busy,true),busy);noRoomClashes(moved);
 const n=moved.bookings.find(b=>b.team==='Nordvelen');assert.ok(n.room);assert.notEqual(n.room,'away1');assert.deepEqual(n.sharingWith,[]);assert.equal(moved.bookings.find(b=>b.guest).room,'away1');assert.ok(moved.notes.some(n=>n.includes('Nordvelen')&&n.includes('Wegen eines Spiels')&&n.includes('1. Auswärtskabine')));
}
// No free cabin: keep Nordvelen unassigned instead of mixing or guessing a gender.
p=plan([training('1. Herren'),training('2. Herren'),training('3. Herren'),training('Alte Herren'),training('Nordvelen')]);noRoomClashes(p);
for(const b of p.bookings.filter(b=>b.room))assert.deepEqual(b.sharingWith,[]);
assert.ok(p.conflicts.length);for(const b of p.bookings.filter(b=>!b.room))assert.match(b.reason,/Keine konfliktfreie/);
console.log('PASS: Nordvelen exclusive away room, repeated Thursdays, guest priority and concrete move alerts; unknown teams still require clarification.');

// Old installed training files remain compatible without overwriting local edits.
p=plan([training('SF Nordvelen')]);assert.equal(p.bookings[0].team,'Nordvelen');assert.equal(p.bookings[0].room,'away1');assert.equal(p.bookings[0].playerCount,10);assert.equal(p.bookings[0].teamCount,1);
