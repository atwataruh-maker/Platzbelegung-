import assert from 'node:assert/strict';
import fs from 'node:fs';
import {build} from 'esbuild';
const b=await build({stdin:{contents:"export * from './lib/hall-plan';",resolveDir:process.cwd(),loader:'ts'},bundle:true,write:false,format:'esm'});
const {hallDay,inSeason,germanDate}=await import('data:text/javascript;base64,'+Buffer.from(b.outputFiles[0].text).toString('base64'));
const plan=JSON.parse(fs.readFileSync('server/hallenplan.json','utf8'));
// Data checks: valid halls, times, weekdays and no overlaps within one hall.
const halls=new Set(plan.halls.map(h=>h.id));
assert.ok(plan.validFrom<plan.validTo&&/^\d{4}-\d\d-\d\d$/.test(plan.validFrom)&&/^\d{4}-\d\d-\d\d$/.test(plan.validTo));
for(const s of plan.slots){
 assert.ok(halls.has(s.hall),'hall '+s.hall);assert.ok(Number.isInteger(s.day)&&s.day>=0&&s.day<=6,'day');
 assert.match(s.start,/^\d\d:\d\d$/);assert.match(s.end,/^\d\d:\d\d$/);assert.ok(s.start<s.end,`${s.start}-${s.end}`);
 if('team' in s)assert.ok(typeof s.team==='string'&&s.team.trim(),'team name');
}
for(const h of halls)for(let d=0;d<7;d++){const list=plan.slots.filter(s=>s.hall===h&&s.day===d).sort((a,c)=>a.start.localeCompare(c.start));for(let i=1;i<list.length;i++)assert.ok(list[i-1].end<=list[i].start,`overlap ${h} day ${d}`);}
// Transcription of the supplied plan (team trainings).
const teams=plan.slots.filter(s=>s.team).map(s=>`${s.day} ${s.hall} ${s.start}-${s.end} ${s.team}`);
assert.deepEqual(teams,['1 walburgis 17:30-19:00 E3-Junioren','2 paulus 17:00-18:00 E1-Juniorinnen','2 paulus 18:00-19:30 E2-Junioren','3 walburgis 17:00-18:30 G-Junioren','3 walburgis 18:30-19:30 F1-Junioren','4 walburgis 17:00-18:00 F2-Junioren','6 paulus 10:00-11:30 Futziball-Mannschaft']);
// Day view: Tuesday 24.11.2026, sorted per hall, free times flagged.
const tue=hallDay(plan,'2026-11-24');
assert.equal(tue.active,true);assert.equal(tue.teams,2);assert.equal(tue.free,3);
assert.deepEqual(tue.halls.find(h=>h.id==='paulus').slots.map(s=>`${s.start} ${s.team??'frei'}`),['16:00 frei','17:00 E1-Juniorinnen','18:00 E2-Junioren','19:30 frei']);
assert.deepEqual(tue.halls.find(h=>h.id==='walburgis').slots.map(s=>s.team??'frei'),['frei']);
const fri=hallDay(plan,'2026-11-27');assert.equal(fri.active,true);assert.ok(fri.halls.every(h=>!h.slots.length));
const sun=hallDay(plan,'2026-11-22');assert.equal(sun.teams,0);assert.equal(sun.free,2);
// Validity: from 19.11.2026 through 31.03.2027.
assert.equal(inSeason(plan,'2026-11-18'),false);assert.equal(inSeason(plan,'2026-11-19'),true);assert.equal(inSeason(plan,'2027-03-31'),true);assert.equal(inSeason(plan,'2027-04-01'),false);
assert.equal(hallDay(plan,'2027-04-06').active,false);assert.ok(hallDay(plan,'2027-04-06').halls.every(h=>!h.slots.length));
assert.equal(hallDay(null,'2026-11-24').active,false);
assert.equal(germanDate('2027-03-31'),'31.03.2027');
console.log('PASS: Hallenplan data, transcription, per-hall day view, free times, Friday/Sunday, validity window.');
