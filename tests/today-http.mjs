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
 console.log('PASS: PHP /app/api/heute day-only schedule, shared allocation, partial source, invalid date and existing API compatibility.');
}finally{server.kill();fs.rmSync(root,{recursive:true,force:true});}
