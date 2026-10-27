import assert from 'node:assert/strict';
import {build} from 'esbuild';
const b=await build({stdin:{contents:"export * from './lib/home-today';export * from './lib/matches';",resolveDir:process.cwd()},bundle:true,write:false,format:'esm'});
const {todayCards,sevenDays}=await import('data:text/javascript;base64,'+Buffer.from(b.outputFiles[0].text).toString('base64'));
const date='2026-10-29';
const valid=()=>({date,schedule:{from:date,to:date,matches:[],retrievedAt:date+'T10:00:00Z',stale:false,provisional:false,omittedRows:0},allocation:{date,bookings:[],changes:[],conflicts:[],warnings:[],automatic:true,cabins:{bookings:[],confirmed:true,conflicts:[]}}});
const cards=d=>todayCards(date,d,false,false);
assert.equal(cards(valid())[0].title,'Heute keine Spiele');assert.equal(cards(valid())[1].title,'Reguläre Platzbelegung');assert.equal(cards(valid())[2].title,'Reguläre Kabinenbelegung');
for(const flag of ['stale','provisional','omittedRows']){const d=valid();d.schedule[flag]=flag==='omittedRows'?1:true;assert.ok(cards(d).every(c=>c.warning));assert.ok(cards(d).every(c=>!c.title.startsWith('Reguläre')));}
for(const d of [null,{...valid(),date:'2026-10-28'},{...valid(),schedule:null}])assert.ok(cards(d).every(c=>c.warning));
assert.ok(todayCards(date,valid(),false,true).every(c=>c.title==='Abgleich derzeit nicht möglich'));
assert.ok(todayCards(date,null,true,false).every(c=>!c.title.startsWith('Reguläre')));
let d=valid();d.schedule.matches=[{id:'1',date,time:'19:00',category:'Herren',home:'Gast',away:'VfL Ramsdorf',location:'Auswärts',status:'Ausfall'},{id:'2',date,time:'17:30',category:'B-Junioren',home:'VfL Ramsdorf',away:'Gast',location:'Heim',status:'',result:{home:2,away:1,confirmed:true}},{id:'3',date:'2026-10-30',time:'15:00',home:'Tomorrow',away:'Guest'}];
assert.equal(cards(d)[0].title,'2 Spiele heute');assert.match(cards(d)[0].lines[0],/2:1/);assert.match(cards(d)[0].lines[1],/Auswärts[\s\S]*Ausfall/);assert.ok(!JSON.stringify(cards(d)).includes('Tomorrow'));
d.allocation.changes=[{team:'E1',time:'17:30–19:00',from:'Kunstrasen',to:'Rasen',reason:'Heimspiel B-Junioren'}];d.allocation.cabins.notes=['Nordvelen · Wegen eines Spiels von 1. Auswärtskabine in 3. Heimkabine verlegt.'];assert.match(cards(d)[1].lines[0],/Kunstrasen → Rasen\nGrund: Heimspiel/);assert.match(cards(d)[2].lines[0],/Wegen eines Spiels/);
d.allocation.conflicts=['Keine freie Fläche für E1'];assert.equal(cards(d)[1].title,'Heute ist Abstimmung nötig');
d.allocation.cabins.conflicts=['E2 ohne Kabine'];assert.equal(cards(d)[2].title,'Heute ist Abstimmung nötig');
assert.equal(sevenDays(new Date('2026-10-29T23:01:00Z')).from,'2026-10-30');
console.log('PASS: daily cards, empty days, home/away, scores/cancellations, moves/reasons, conflicts, incomplete sources and Berlin date rollover.');
