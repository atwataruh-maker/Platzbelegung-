import assert from 'node:assert/strict';
// Run against a disposable LOCAL database only. Env: TEST_SETUP_TOKEN, TEST_ADMIN_PASSWORD.
const base=process.argv[2]??'http://127.0.0.1:8080';
assert.ok(/^http:\/\/127\.0\.0\.1:\d+(?:\/[a-z0-9-]+)?$/.test(base),'Integration tests are restricted to localhost.');
const origin=new URL(base).origin;
let cookie='',token='';
async function request(path,method='GET',body,options={}){
 const headers={Cookie:cookie,...options.headers};if(method!=='GET'){headers.Origin=origin;headers['Content-Type']='application/json';headers['X-CSRF-Token']=token;}
 if(options.noCsrf)delete headers['X-CSRF-Token'];if(options.origin)headers.Origin=options.origin;
 const r=await fetch(base+path,{method,headers,body:body===undefined?undefined:JSON.stringify(body)});
 const cookies=r.headers.getSetCookie();if(cookies.length)cookie=cookies.map(s=>s.split(';')[0]).join('; ');
 const text=await r.text();let data;try{data=JSON.parse(text);}catch{data=text;}
 return {status:r.status,data,headers:r.headers};
}
const password=process.env.TEST_ADMIN_PASSWORD;assert.ok(password&&password.length>=12);const setup=process.env.TEST_SETUP_TOKEN;assert.ok(setup);
let r=await request('/install.php');assert.equal(r.status,200);assert.equal(typeof r.data,'string');const csrf=r.data.match(/name="csrf" value="([a-f0-9]+)"/)?.[1];assert.ok(csrf);
const install=await fetch(base+'/install.php',{method:'POST',headers:{Cookie:cookie,Origin:origin,'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({csrf,token:setup,username:'vfl-test-admin',password})});assert.equal(install.status,200);assert.match(await install.text(),/Installation abgeschlossen/);
assert.equal((await request('/install.php')).status,403);
r=await request('/api/auth');token=r.data.csrf;assert.equal(r.data.canManage,false);
assert.equal((await request('/api/helfer','POST',{})).status,403);
assert.equal((await request('/api/auth','POST',{username:'vfl-test-admin',password},{noCsrf:true})).status,403);
assert.equal((await request('/api/auth','POST',{username:'vfl-test-admin',password},{origin:'https://evil.example'})).status,403);
assert.equal((await request('/api/auth','POST',{username:'vfl-test-admin',password:'wrong-password'})).status,401);
const before=cookie;r=await request('/api/auth','POST',{username:'vfl-test-admin',password});assert.equal(r.status,200);assert.equal(r.data.canManage,true);assert.notEqual(cookie,before);token=r.data.csrf;
const body={id:crypto.randomUUID(),title:'Fliesenleger gesucht',description:'Acht Stunden Arbeit in den Umkleiden.',category:'Handwerk',hours:8,location:'Umkleiden',timeframe:'nach Absprache',contact:'Testkontakt'};
r=await request('/api/helfer','POST',body);assert.equal(r.status,201,JSON.stringify(r.data));assert.equal(r.data.record.hours,8);assert.equal(r.data.record.status,'open');assert.ok(!('created_by' in r.data.record));
assert.equal((await request('/api/helfer','POST',body)).status,201);
assert.equal((await request('/api/helfer','POST',{...body,title:'Anderer Titel'})).status,409);
assert.equal((await request('/api/helfer','POST',{...body,id:crypto.randomUUID(),hours:'8'})).status,400);
r=await request('/api/helfer','PATCH',{id:body.id,expectedStatus:'open',status:'completed'});assert.equal(r.status,200);assert.equal(r.data.record.status,'completed');assert.ok(r.data.record.completed_at);
r=await request('/api/helfer','PATCH',{id:body.id,expectedStatus:'completed',status:'open'});assert.equal(r.status,200);assert.equal(r.data.record.completed_at,null);
r=await request('/api/helfer');assert.equal(r.data.records.length,1);assert.equal(r.data.canManage,true);
r=await request('/api/auth','DELETE',{});assert.equal(r.status,200);token=r.data.csrf;
assert.equal((await request('/api/helfer','PATCH',{id:body.id,expectedStatus:'open',status:'completed'})).status,403);
r=await request('/api/helfer');assert.equal(r.data.canManage,false);assert.equal(r.data.records.length,1);
for(const p of ['/','/platzbelegung','/kabinenbelegung','/ehrenamt','/admin']){r=await request(p);assert.equal(r.status,200,p);assert.match(r.data,/window.VFL=/);assert.match(r.data,/assets\/index-/);assert.ok(r.headers.get('content-security-policy'));}
assert.equal((await request('/api/spiele?date=2026-02-31')).status,400);
for(const p of ['/config.php','/server/training.json','/var/','/frontend-dist/index.html'])assert.equal((await request(p)).status,403,p);
console.log('PASS: PHP/MySQL installer, install lock, admin login/logout/session rotation, CSRF/origin checks, anonymous write rejection, create/idempotence/conflict/validation, complete/reopen/persistence, pages and blocked files.');
