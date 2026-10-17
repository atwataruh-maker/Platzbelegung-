import assert from 'node:assert/strict';
import fs from 'node:fs';
import ts from 'typescript';
import { Miniflare, createFetchMock } from 'miniflare';
const compile = (path) => ts.transpileModule(fs.readFileSync(path, 'utf8'), { compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ESNext } }).outputText;
const pdf = fs.readFileSync('public/plan-snapshot.pdf');
const {build} = await import('esbuild');
const compiled = await build({stdin:{contents:"import {GET} from './app/api/plan/route.ts';export default {fetch:GET};",resolveDir:process.cwd(),sourcefile:'test.ts'},bundle:true,write:false,format:'esm',platform:'browser'});
const {SOURCE,KNOWN_HASH} = await import('data:text/javascript;base64,'+Buffer.from(compile('lib/plan.ts')).toString('base64'));
const {createHash} = await import('node:crypto');
assert.equal(createHash('sha256').update(fs.readFileSync('public'+SOURCE)).digest('hex'),KNOWN_HASH);
assert.deepEqual(pdf,fs.readFileSync('public'+SOURCE));
const mf = new Miniflare({modules:true,compatibilityDate:'2026-05-15',script:compiled.outputFiles[0].text});
try {
 const response = await mf.dispatchFetch('https://test.local/api/plan',{redirect:'manual'});
 assert.equal(response.status,302);assert.equal(response.headers.get('location'),SOURCE);
 console.log('PASS: active PDF checksum, matching fallback copy, original endpoint points to uploaded season plan.');
} finally {await mf.dispose();}
const { loadPlanBytes } = await import('data:text/javascript;base64,' + Buffer.from(compile('lib/load-plan.ts')).toString('base64'));
const realFetch = globalThis.fetch;
try {
 const calls = [];
 globalThis.fetch = async url => { calls.push(url); return url === '/api/plan' ? new Response('Unavailable', { status: 502 }) : new Response(pdf); };
 const fallback = await loadPlanBytes(new AbortController().signal);
 assert.equal(fallback.isSnapshot, true); assert.deepEqual(calls, ['/api/plan','/plan-snapshot.pdf']);
 globalThis.fetch = async () => new Response(pdf);
 assert.equal((await loadPlanBytes(new AbortController().signal)).isSnapshot, false);
 globalThis.fetch = async () => new Response('Unavailable', { status: 502 });
 await assert.rejects(loadPlanBytes(new AbortController().signal), /gesicherte Kopie/);
 const cancelled = new AbortController(); cancelled.abort();
 await assert.rejects(loadPlanBytes(cancelled.signal), { name: 'AbortError' });
 console.log('Client loading: live, marked snapshot, complete outage and cancellation verified.');
} finally { globalThis.fetch = realFetch; }
