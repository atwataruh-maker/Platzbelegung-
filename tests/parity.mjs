import fs from 'node:fs';
import {spawnSync} from 'node:child_process';
import assert from 'node:assert/strict';
import {build} from 'esbuild';
// Reuse all existing cabin/pitch regression scenarios against both implementations.
let source=fs.readFileSync('scripts/check-cabins.mjs','utf8');
source=source.replace('const {allocate,allocateCabins,','let {allocate,allocateCabins,');
source=source.replace("const date='2026-10-19';",`const captured=[];
const originalAllocate=allocate,originalCabins=allocateCabins;
allocate=(...args)=>{const expected=originalAllocate(...args);captured.push({kind:'pitch',args,expected});return expected;};
allocateCabins=(...args)=>{const expected=originalCabins(...args);captured.push({kind:'cabins',args,expected});return expected;};
const date='2026-10-19';`);
source=source.replace("from 'esbuild'", 'from '+JSON.stringify(import.meta.resolve('esbuild')));
source+='\nexport default captured;';
const captured=(await import('data:text/javascript;base64,'+Buffer.from(source).toString('base64'))).default;
const b=await build({stdin:{contents:"export * from './lib/matches';export * from './lib/score-font';",resolveDir:process.cwd()},bundle:true,write:false,format:'esm'});
const {parseMatches,CLUB_ID,scoreFontMap}=await import('data:text/javascript;base64,'+Buffer.from(b.outputFiles[0].text).toString('base64'));
const row=(score,category='C-Junioren',url='spiel/test/-/id/game1')=>`<tr class="row-headline"><td>Donnerstag, 15.10.2026 - 18:00 Uhr | ${category} | Liga</td></tr><tr><td class="column-club"><a href="/${CLUB_ID}"><div class="club-name">VfL Ramsdorf</div></a></td><td class="column-club"><div class="club-name">Gast &amp; Partner</div></td><td class="column-score"><a href="https://www.fussball.de/${url}">${score}</a></td></tr>`;
const table=rows=>'<table class="club-matchplan-table"><tbody>'+rows+'</tbody></table>';
for(const score of ['', 'Ausfall','Absetzung','Abbruch','Nichtantritt HEIM','o.E.','02.11.2026 18:00','<span class="score-left">0</span>:<span class="score-right">2</span><span class="icon-verified"></span>','<span data-obfuscation="abc" class="score-left">&#xE001;</span>:<span data-obfuscation="abc" class="score-right">&#xE002;</span>']){
 for(const fonts of [{},{abc:{'\uE001':'1','\uE002':'0'}}]){
 const args=[table(row(score)),'2026-10-15','2026-10-15',fonts];captured.push({kind:'matches',args,expected:parseMatches(...args)});
 }
}
for(const html of ['<table class="club-matchplan-table"><thead><tr><th>Spiele</th></tr></thead></table>',table(row('','F-Juniorinnen','spieltag/test/-/staffel/team1')),table(row('')+'<tr class="row-headline"><td>bad</td></tr>')]){const args=[html,'2026-10-15','2026-10-15'];captured.push({kind:'matches',args,expected:parseMatches(...args)});}
// A tiny synthetic TTF with digit names and a format 4 cmap; no external fixture files.
const font=Buffer.alloc(512);font.writeUInt32BE(0x00010000,0);font.writeUInt16BE(2,4);font.write('post',12);font.writeUInt32BE(64,20);font.write('cmap',28);font.writeUInt32BE(160,36);font.writeUInt32BE(0x00020000,64);font.writeUInt16BE(3,96);font.writeUInt16BE(0,98);font.writeUInt16BE(19,100);font.writeUInt16BE(20,102);font.writeUInt16BE(1,162);font.writeUInt32BE(12,168);const sub=172;font.writeUInt16BE(4,sub);font.writeUInt16BE(32,sub+2);font.writeUInt16BE(4,sub+6);font.writeUInt16BE(0xe002,sub+14);font.writeUInt16BE(0xffff,sub+16);font.writeUInt16BE(0xe001,sub+20);font.writeUInt16BE(0xffff,sub+22);font.writeUInt16BE((1-0xe001)&65535,sub+24);font.writeUInt16BE(1,sub+26);
captured.push({kind:'font',args:[font.toString('base64')],expected:scoreFontMap(font.buffer.slice(font.byteOffset,font.byteOffset+font.byteLength))});
const result=spawnSync(process.env.PHP_BIN||'php',['tests/parity.php'],{input:JSON.stringify(captured.map(({kind,args})=>({kind,args}))),encoding:'utf8',maxBuffer:20000000});
assert.equal(result.status,0,result.stderr+result.stdout);
const actual=JSON.parse(result.stdout);
function normalize(x){if(Array.isArray(x))return x.map(normalize).sort((a,b)=>JSON.stringify(a).localeCompare(JSON.stringify(b)));if(x&&typeof x==='object')return Object.fromEntries(Object.entries(x).filter(([,v])=>v!==undefined).sort(([a],[b])=>a.localeCompare(b)).map(([k,v])=>[k,normalize(v)]));return x;}
for(let i=0;i<captured.length;i++)assert.deepEqual(normalize(actual[i]),normalize(captured[i].expected),`PHP/TypeScript parity case ${i}: ${captured[i].kind}`);
console.log(`PASS: ${captured.length} PHP/TypeScript comparisons: pitches, cabins, Oct 15/16, gender sharing, exclusive women's rooms, dates, matches, results, score fonts.`);
