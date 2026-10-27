import assert from 'node:assert/strict';
import {spawn} from 'node:child_process';
import {createRequire} from 'node:module';
const require=createRequire(import.meta.url);
const {chromium}=require(process.env.PLAYWRIGHT_PATH||'playwright');
const server=spawn(process.execPath,['node_modules/vite/bin/vite.js','--config','vite.strato.config.ts','--host','127.0.0.1','--port','4175'],{stdio:'pipe'});
let browser;
try{
 await new Promise((resolve,reject)=>{server.stdout.on('data',d=>{if(String(d).includes('4175'))resolve();});server.on('error',reject);setTimeout(()=>reject(Error('Preview server timeout')),15000).unref();});
 browser=await chromium.launch({headless:true,executablePath:process.env.CHROMIUM_PATH,args:['--no-sandbox']});
 const page=await browser.newPage({viewport:{width:390,height:844},isMobile:true,hasTouch:true});
 const errors=[];page.on('pageerror',e=>errors.push(String(e)));
 await page.addInitScript(()=>{window.VFL={base:'/app',csrf:'test'};});
 await page.clock.install({time:new Date('2026-10-29T10:00:00Z')});
 let mode='normal',dates=[];
 await page.route('**/api/heute?*',async route=>{
  const date=new URL(route.request().url()).searchParams.get('date');dates.push(date);
  if(mode==='error')return route.fulfill({status:502,body:'{}'});
  const data={date,schedule:{from:date,to:date,matches:[],retrievedAt:date+'T10:00:00Z',stale:mode==='stale',provisional:false,omittedRows:0},allocation:{date,bookings:[],changes:[],conflicts:[],warnings:[],automatic:mode!=='stale',cabins:{bookings:[],confirmed:mode!=='stale',conflicts:[]}}};
  if(mode==='changes'){data.schedule.matches=[{id:'demo',date,time:'18:30',category:'B-Junioren',home:'VfL Ramsdorf',away:'Gastmannschaft',location:'Heim',status:''}];data.allocation.changes=[{team:'E1-Junioren',time:'17:30–19:00',from:'Kunstrasen vorne',to:'Trainingsrasen',reason:'Heimspiel der B-Junioren'}];data.allocation.cabins.notes=['Nordvelen · 18:45–20:45: Wegen eines Spiels von 1. Auswärtskabine in 3. Heimkabine verlegt.'];}
  await route.fulfill({json:data});
 });
 await page.goto('http://127.0.0.1:4175/app/');
 const title=()=>page.locator('.today-slide:visible h2').textContent();
 await page.getByRole('heading',{name:'Heute keine Spiele'}).waitFor();
 assert.equal(await page.locator('.today-slide:visible').count(),1);
 await page.clock.fastForward(4000);assert.equal(await title(),'Heute keine Spiele');
 await page.clock.fastForward(1100);assert.equal(await title(),'Reguläre Platzbelegung');
 await page.clock.fastForward(5000);assert.equal(await title(),'Reguläre Kabinenbelegung');
 await page.getByRole('button',{name:'Automatischen Wechsel pausieren'}).click();await page.locator('h1').click();await page.clock.fastForward(10000);assert.equal(await title(),'Reguläre Kabinenbelegung');
 await page.getByRole('button',{name:'Nächste Information'}).click();assert.equal(await title(),'Heute keine Spiele');
 await page.locator('.today-slides').evaluate(el=>{const start=new Touch({identifier:1,target:el,clientX:300,clientY:300}),end=new Touch({identifier:1,target:el,clientX:120,clientY:305});el.dispatchEvent(new TouchEvent('touchstart',{bubbles:true,touches:[start]}));el.dispatchEvent(new TouchEvent('touchend',{bubbles:true,changedTouches:[end]}));});assert.equal(await title(),'Reguläre Platzbelegung');
 assert.match(await page.locator('.today-slide:visible a').getAttribute('href'),/^\/app\/platzbelegung\?date=2026-10-29#pitch-title$/);
 assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
 for(const width of [320,390,768,1280]){await page.setViewportSize({width,height:900});assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,`Overflow at ${width}`);}
 await page.setViewportSize({width:390,height:844});mode='changes';await page.reload();await page.getByRole('heading',{name:'1 Spiel heute'}).waitFor();await page.screenshot({path:'work/home-games.png',fullPage:true});
 await page.getByRole('button',{name:'Platzbelegung',exact:true}).click();assert.match(await page.locator('.today-slide:visible').textContent(),/Grund: Heimspiel/);await page.screenshot({path:'work/home-pitch.png',fullPage:true});
 await page.getByRole('button',{name:'Kabinenbelegung',exact:true}).click();assert.match(await page.locator('.today-slide:visible').textContent(),/Wegen eines Spiels/);await page.screenshot({path:'work/home-cabins.png',fullPage:true});
 assert.equal(await page.locator('.today-photo').evaluateAll(imgs=>imgs.every(i=>i.complete&&i.naturalWidth>0)),true);
 assert.equal(await page.locator('.club-logo').evaluate(i=>i.complete&&i.naturalWidth>0),true);
 mode='stale';await page.reload();await page.getByRole('heading',{name:'Spielplan noch unvollständig'}).waitFor();await page.getByRole('button',{name:'Platzbelegung',exact:true}).click();assert.equal(await title(),'Belegung noch vorläufig');
 mode='error';await page.reload();await page.getByRole('heading',{name:'Abgleich derzeit nicht möglich'}).waitFor();assert.equal(await page.getByRole('button',{name:'Erneut laden'}).count(),1);
 mode='normal';await page.getByRole('button',{name:'Erneut laden'}).click();await page.getByRole('heading',{name:'Heute keine Spiele'}).waitFor();
 await page.emulateMedia({reducedMotion:'reduce'});await page.reload();await page.getByRole('heading',{name:'Heute keine Spiele'}).waitFor();await page.clock.fastForward(6000);assert.equal(await title(),'Heute keine Spiele');
 // Day rollover must fetch the new Berlin day, never report yesterday's regular plan.
 await page.clock.setSystemTime(new Date('2026-10-29T23:01:00Z'));await page.clock.fastForward(61000);await page.waitForFunction(()=>document.querySelector('.today-heading time').getAttribute('datetime')==='2026-10-30');assert.ok(dates.includes('2026-10-30'));
 assert.deepEqual(errors,[]);console.log('PASS: 5s rotation, pause, swipe, daily links, responsive layouts, photos/logo, real-change cards, stale/error recovery, reduced motion and midnight rollover.');
}finally{await browser?.close();server.kill();}
