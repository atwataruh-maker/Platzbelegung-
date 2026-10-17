import {build} from 'esbuild';
import {Miniflare} from 'miniflare';
import assert from 'node:assert/strict';
import {fetch as sourceFetch,EnvHttpProxyAgent} from 'undici';
const worker=await build({stdin:{contents:"import {GET} from './app/api/belegung/route.ts';export default {fetch:GET};",resolveDir:process.cwd(),sourcefile:'check.ts'},bundle:true,write:false,format:'esm',platform:'browser'});
const proxy=new EnvHttpProxyAgent();
const runtime=new Miniflare({modules:true,script:worker.outputFiles[0].text,compatibilityDate:'2026-05-15',outboundService:async req=>{const upstream=await sourceFetch(req.url,{dispatcher:proxy,redirect:'manual',headers:Object.fromEntries(req.headers)});return new Response(await upstream.arrayBuffer(),{status:upstream.status,headers:Object.fromEntries(upstream.headers)});}});
try{
 const response=await runtime.dispatchFetch('https://test.local/api/belegung?date='+(process.argv[2]??'2026-10-16'));
 const r=await response.json();assert.equal(response.status,200,JSON.stringify(r));assert.equal(r.source.state,'verified');
 console.log(JSON.stringify({date:r.date,source:r.source,automatic:r.automatic,changes:r.changes,conflicts:r.conflicts,warnings:r.warnings,cabins:r.cabins,bookings:r.bookings.filter(b=>b.kind!=='reserve')},null,2));
}finally{await runtime.dispose();await proxy.close();}
