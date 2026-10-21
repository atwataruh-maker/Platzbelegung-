import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import {build} from 'esbuild';
// Same small two-digit TrueType fixture as the decoder parity test.
const font=Buffer.alloc(512);font.writeUInt32BE(0x00010000,0);font.writeUInt16BE(2,4);font.write('post',12);font.writeUInt32BE(64,20);font.write('cmap',28);font.writeUInt32BE(160,36);font.writeUInt32BE(0x00020000,64);font.writeUInt16BE(3,96);font.writeUInt16BE(0,98);font.writeUInt16BE(19,100);font.writeUInt16BE(20,102);font.writeUInt16BE(1,162);font.writeUInt32BE(12,168);const sub=172;font.writeUInt16BE(4,sub);font.writeUInt16BE(32,sub+2);font.writeUInt16BE(4,sub+6);font.writeUInt16BE(0xe002,sub+14);font.writeUInt16BE(0xffff,sub+16);font.writeUInt16BE(0xe001,sub+20);font.writeUInt16BE(0xffff,sub+22);font.writeUInt16BE((1-0xe001)&65535,sub+24);font.writeUInt16BE(1,sub+26);
const r=spawnSync(process.env.PHP_BIN||'php',['tests/reliability.php'],{input:font.toString('base64'),encoding:'utf8'});assert.equal(r.status,0,r.stdout+r.stderr);console.log(r.stdout.trim());
globalThis.window={VFL:{base:'/app',csrf:'outdated-token'}};
const calls=[];globalThis.fetch=async(path,init={})=>{calls.push({path,init});return Response.json(path.endsWith('/api/auth')?{csrf:'fresh-token',canManage:true}:{record:{id:'same-id'}});};
const b=await build({entryPoints:['frontend/api.ts'],bundle:true,write:false,format:'esm'});const {apiFetch}=await import('data:text/javascript;base64,'+Buffer.from(b.outputFiles[0].text).toString('base64'));
await apiFetch('/api/helfer',{method:'POST',headers:{'Content-Type':'application/json'},body:'{"id":"same-id"}'});
assert.equal(calls.length,2);assert.equal(calls[0].path,'/app/api/auth');assert.equal(calls[1].path,'/app/api/helfer');assert.equal(calls[1].init.headers.get('X-CSRF-Token'),'fresh-token');assert.equal(calls[1].init.body,'{"id":"same-id"}');assert.equal(calls[1].init.credentials,'same-origin');
calls.length=0;globalThis.fetch=async(path)=>{calls.push(path);return Response.json({error:'Session unavailable'},{status:503});};await assert.rejects(apiFetch('/api/helfer',{method:'POST'}),/Sitzung/);assert.deepEqual(calls,['/app/api/auth']);
console.log('PASS: helper writes renew CSRF at /app, preserve payload, and stop on session failure without duplicate writes.');
