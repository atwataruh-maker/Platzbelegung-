import assert from 'node:assert/strict';
import fs from 'node:fs';import os from 'node:os';import path from 'node:path';import {spawn} from 'node:child_process';
const root=fs.mkdtempSync(path.join(os.tmpdir(),'vfl-today-'));const serverRoot=path.join(root,'server');
fs.cpSync('server',serverRoot,{recursive:true});fs.copyFileSync('index.php',path.join(root,'index.php'));
fs.mkdirSync(path.join(root,'var/cache'),{recursive:true});
fs.writeFileSync(path.join(root,'config.php'),"<?php return ['app_url'=>'http://127.0.0.1:4187/app'];");
const write=(day,schedule)=>fs.writeFileSync(path.join(root,`var/cache/matches-v2-${day}-${day}.json`),JSON.stringify({time:Math.floor(Date.now()/1000),data:schedule}));
const date='2026-10-29',base={from:date,to:date,matches:[],retrievedAt:new Date().toISOString(),stale:false,provisional:false,omittedRows:0};write(date,base);
const server=spawn(process.env.PHP_BIN||'php',['-S','127.0.0.1:4187',path.resolve('tools/router.php')],{env:{...process.env,VFL_DOCROOT:root},stdio:'pipe'});
try{
 await new Promise((resolve,reject)=>{server.stderr.on('data',d=>{if(String(d).includes('started'))resolve();});server.on('error',reject);setTimeout(()=>reject(Error('PHP timeout')),10000).unref();});
 let response=await fetch('http://127.0.0.1:4187/app/api/heute?date='+date);assert.equal(response.status,200);let data=await response.json();assert.equal(data.date,date);assert.equal(data.schedule.to,date);assert.equal(data.allocation.date,date);assert.equal(data.allocation.cabins.confirmed,true);assert.equal(data.allocation.cabins.bookings.find(b=>b.team==='E3-Junioren').room,'away1');
 write(date,{...base,omittedRows:1});data=await (await fetch('http://127.0.0.1:4187/app/api/heute?date='+date)).json();assert.equal(data.allocation.automatic,false);assert.equal(data.allocation.cabins.confirmed,false);
 response=await fetch('http://127.0.0.1:4187/app/api/heute?date=2026-02-30');assert.equal(response.status,400);
 // Existing day-plan endpoint remains compatible.
 data=await (await fetch('http://127.0.0.1:4187/app/api/belegung?date='+date)).json();assert.ok(Array.isArray(data.cabins.bookings));assert.equal(data.date,date);
 // New homepage endpoints work below /app and retain exact elapsed-time filtering.
 const now=new Date(),berlin=new Intl.DateTimeFormat('en-CA',{timeZone:'Europe/Berlin',year:'numeric',month:'2-digit',day:'2-digit'});
 const from=berlin.format(new Date(now.getTime()-48*3600000)),to=berlin.format(now);
 const kickoff=new Date(now.getTime()-2*3600000),kickoffDate=berlin.format(kickoff),kickoffTime=new Intl.DateTimeFormat('en-GB',{timeZone:'Europe/Berlin',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).format(kickoff);
 fs.writeFileSync(path.join(root,`var/cache/matches-v2-${from}-${to}.json`),JSON.stringify({time:Math.floor(Date.now()/1000),data:{...base,from,to,matches:[{id:'result',date:kickoffDate,time:kickoffTime,result:{home:0,away:0,confirmed:true}}]}}));
 data=await (await fetch('http://127.0.0.1:4187/app/api/ergebnisse')).json();assert.equal(data.matches.length,1);assert.equal(data.matches[0].result.home,0);assert.ok(data.matches[0].kickoffAt);assert.equal(Date.parse(data.until)-Date.parse(data.since),48*3600000);
 fs.writeFileSync(path.join(root,'var/cache/bomber-current.json'),JSON.stringify({time:Math.floor(Date.now()/1000),data:{url:'https://www.vfl-ramsdorf.de/bomber/test.pdf',label:'Testausgabe',stale:false}}));
 data=await (await fetch('http://127.0.0.1:4187/app/api/bomber')).json();assert.equal(data.label,'Testausgabe');
 fs.mkdirSync(path.join(root,'frontend-dist'));fs.writeFileSync(path.join(root,'frontend-dist/index.html'),'<html><head></head><body>Bomber route</body></html>');
 response=await fetch('http://127.0.0.1:4187/app/bomber');assert.equal(response.status,200);assert.match(await response.text(),/window.VFL/);
 // Breitensport: maintained offers plus cached course data; page route below /app.
 fs.writeFileSync(path.join(root,'var/cache/breitensport-kurse.json'),JSON.stringify({time:Math.floor(Date.now()/1000),data:{courses:[{id:56,title:'Line Dance - Level I',seatsLeft:2}],retrievedAt:new Date().toISOString(),stale:false}}));
 fs.writeFileSync(path.join(root,'var/cache/breitensport-zeiten.json'),JSON.stringify({time:Math.floor(Date.now()/1000),data:{entries:[{page:'seniorensport',title:'Aerobic',leader:'Marion Schmidt',venue:'Alte Molkerei - Kursraum',audience:null,day:3,start:'19:00',end:'20:00'}],pages:{seniorensport:true},retrievedAt:new Date().toISOString(),stale:false}}));
 data=await (await fetch('http://127.0.0.1:4187/app/api/breitensport')).json();
 const aerobic=data.offers.find(o=>o.id==='aerobic').sessions[0];assert.equal(aerobic.start,'19:00');assert.equal(aerobic.live.state,'updated');assert.equal(aerobic.live.planned.start,'19:30');
 assert.equal(data.offers.find(o=>o.id==='senioren-sport').sessions[0].live.state,'missing');assert.equal(data.offers.find(o=>o.id==='trampolin').sessions[0].live.state,'unchecked');assert.ok(data.live.retrievedAt);assert.equal(data.offers.length,18);assert.equal(data.contacts.length,3);assert.equal(data.courses.courses[0].seatsLeft,2);
 response=await fetch('http://127.0.0.1:4187/app/breitensport?angebot=trampolin');assert.equal(response.status,200);assert.match(await response.text(),/window.VFL/);
 console.log('PASS: /app/api/ergebnisse rolling window and 0:0, /app/api/bomber metadata, /app/bomber route, /app/api/breitensport and /app/breitensport.');
 console.log('PASS: PHP /app/api/heute day-only schedule, shared allocation, partial source, invalid date and existing API compatibility.');
}finally{server.kill();fs.rmSync(root,{recursive:true,force:true});}
