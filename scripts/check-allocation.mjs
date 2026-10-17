import assert from 'node:assert/strict';
import {build} from 'esbuild';
import {Miniflare} from 'miniflare';
import {readFile} from 'node:fs/promises';
const bundled=await build({entryPoints:['lib/pitch-allocation.ts'],bundle:true,write:false,format:'esm',platform:'node'});
const {allocate,overlaps}=await import('data:text/javascript;base64,'+Buffer.from(bundled.outputFiles[0].text).toString('base64'));
const data=await build({entryPoints:['lib/training-plan.ts'],bundle:true,write:false,format:'esm',platform:'node'});
const {TRAINING,FRONT,BACK,TURF,MAIN,trainingsFor}=await import('data:text/javascript;base64,'+Buffer.from(data.outputFiles[0].text).toString('base64'));
const date='2026-10-19';
const game=(id,category='E-Junioren',time='17:00',extra={})=>({id,category,time,date,home:'VfL Ramsdorf',away:'Gast '+id,competition:'Liga',venue:'Ramsdorf',location:'Heim',status:'',url:'https://www.fussball.de/spiel/'+id,...extra});
const schedule=(matches=[],extra={})=>({from:date,to:date,matches,retrievedAt:new Date().toISOString(),stale:false,provisional:false,omittedRows:0,...extra});
const training=(id,fields,start=990,end=1110,small=true)=>({id,day:1,team:id,small,season:'all',segments:[{start,end,fields}]});
function noOverlaps(result){
 const b=result.bookings.filter(x=>x.kind!=='reserve');
 for(let i=0;i<b.length;i++)for(let j=i+1;j<b.length;j++)assert.equal(b[i].segments.some(s=>b[j].segments.some(t=>overlaps(s,t))),false,`${b[i].title} overlaps ${b[j].title}`);
}
assert.equal(TRAINING.length,38);
assert.equal(trainingsFor('2026-10-17').length,0);
assert.equal(trainingsFor('2026-09-24').some(t=>t.team==='SF Nordvelen'),false);
assert.equal(trainingsFor('2026-10-01').some(t=>t.team==='SF Nordvelen'),true);
assert.deepEqual(['19','20','21','22','23'].map(d=>trainingsFor('2026-10-'+d).length),[7,8,8,8,7]);
for(let day=19;day<=25;day++){
 const d=`2026-10-${day}`,r=allocate(d,{...schedule(),from:d,to:d},true);
 assert.equal(r.changes.length,0);assert.equal(r.conflicts.length,0);noOverlaps(r);
}
let r=allocate(date,schedule([game('E')]),true,[training('E2',FRONT)]);
assert.deepEqual(r.bookings.find(b=>b.kind==='match').segments[0].fields,BACK);assert.equal(r.changes.length,0);
r=allocate(date,schedule([game('D','D-Junioren')]),true,[training('E2',FRONT)]);
assert.equal(r.changes.length,1);assert.equal(r.changes[0].to,'Rasen 1/3');noOverlaps(r);
assert.equal(r.bookings.find(b=>b.kind==='training').segments[0].start,990);assert.equal(r.bookings.find(b=>b.kind==='training').segments[0].end,1110);
for(const category of ['D-Junioren','C-Juniorinnen','B-Junioren','A-Junioren'])assert.equal(allocate(date,schedule([game(category,category)]),true,[]).bookings.find(b=>b.kind==='match').segments[0].fields.length,4);
for(const suffix of ['',' II',' III'])assert.deepEqual(allocate(date,schedule([game(suffix,'Herren','17:00',{home:'VfL Ramsdorf'+suffix})]),true,[]).bookings.find(b=>b.kind==='match').segments[0].fields,MAIN);
assert.deepEqual(allocate(date,schedule([game('women','Frauen')]),true,[]).bookings.find(b=>b.kind==='match').segments[0].fields,TURF);
for(const extra of [{location:'Auswärts'},{status:'Ausfall'},{status:'Absetzung'}])assert.equal(allocate(date,schedule([game('away','D-Junioren','17:00',extra)]),true,[training('E2',FRONT)]).changes.length,0);
r=allocate(date,schedule([game('E1'),game('E2')]),true,[]);assert.equal(r.conflicts.length,0);noOverlaps(r);
r=allocate(date,schedule([game('D1','D-Junioren'),game('D2','D-Junioren')]),true,[]);assert.equal(r.conflicts.length,1);
// Boundary: game reservation ends 18:20; a training starting exactly then is unaffected.
r=allocate(date,schedule([game('E')]),true,[training('late',FRONT,1100,1200)]);assert.equal(r.changes.length,0);
const crowded=[training('blocked',TURF,990,1110,false),training('r1',['r1']),training('r2',['r2']),training('r3',['r3']),training('corner',['corner'])];
r=allocate(date,schedule([game('D','D-Junioren')]),true,crowded);assert.equal(r.conflicts.length,1);assert.equal(r.bookings.find(b=>b.id==='blocked').segments.length,0);noOverlaps(r);
// Reallocations never evict unaffected training; main is not needed when regular fields suffice.
r=allocate(date,schedule([game('D','D-Junioren')]),true,[training('one',FRONT),training('two',BACK),training('fixed',['r1'])]);assert.equal(r.changes.length,2);assert.equal(r.bookings.find(b=>b.id==='fixed').moved,undefined);noOverlaps(r);assert.ok(!r.bookings.filter(b=>b.kind==='training').some(b=>b.segments.some(s=>s.fields.some(f=>MAIN.includes(f)))));
for(const sc of [null,schedule([],{stale:true}),schedule([],{omittedRows:1}),schedule([game('unknown','D-Junioren','Zeit offen')]),schedule([game('festival','E-Junioren','17:00',{url:'https://www.fussball.de/spieltag/id'})])]){r=allocate(date,sc,true);assert.equal(r.automatic,false);assert.equal(r.changes.length,0);assert.ok(r.warnings.length);}
assert.equal(allocate(date,schedule(),false).automatic,false);
assert.equal(allocate(date,schedule([game('D','D-Junioren')],{provisional:true}),true).automatic,true);
r=allocate(date,schedule([game('moved','E-Junioren','18:00',{status:'Verlegt: 02.11.2026 18:00'})]),true);assert.equal(r.automatic,true);assert.equal(r.bookings.some(b=>b.kind==='match'),false);
r=allocate(date,schedule([game('moved','E-Junioren','18:00',{status:'Verlegt: 19.10.2026 19:00'})]),true);assert.equal(r.bookings.find(b=>b.kind==='match').kickoff,'19:00');
assert.equal(overlaps({start:100,end:200,fields:FRONT},{start:200,end:300,fields:FRONT}),false);

assert.deepEqual(allocate(date,schedule([game('D','D-Junioren')]),true),allocate(date,schedule([game('D','D-Junioren')]),true));
// The owner-supplied source is independent of the outdated website PDF; match failures still pause allocation.
const api=await build({stdin:{contents:"import {GET} from './app/api/belegung/route.ts';export default {fetch:GET};",resolveDir:process.cwd(),sourcefile:'test.ts'},bundle:true,write:false,format:'esm',platform:'browser'});
for(const mode of ['verified','matches-unavailable']){
 const mf=new Miniflare({modules:true,script:api.outputFiles[0].text,compatibilityDate:'2026-05-15',outboundService:req=>{
  assert.ok(!req.url.includes('Trainingszeit.pdf'),'Old website PDF must not be fetched');
  if(mode==='matches-unavailable')return new Response('down',{status:503});
  return new Response('<div class="club-matchplan-table"><table><thead><tr><th>Datum</th></tr></thead></table></div>');
 }});
 try{
  const res=await mf.dispatchFetch('https://test.local/api/belegung?date='+date);assert.equal(res.status,200);const body=await res.json();assert.equal(body.automatic,mode==='verified');
  assert.equal(body.source.origin,'upload');
  assert.ok(Array.isArray(body.cabins.bookings));assert.equal(body.cabins.confirmed,mode==='verified');
  if(mode==='verified')assert.equal(body.bookings.filter(b=>b.kind==='training').length,7);
  assert.equal((await mf.dispatchFetch('https://test.local/api/belegung?date=2026-02-30')).status,400);
 }finally{await mf.dispose();}
}
console.log('PASS: 38 supplied source sessions; October rule; half/full pitches; seniors; away/cancelled games; overlap/boundaries; stable reassignment; no-capacity conflicts; missing/stale/partial data; uploaded source; API.');
