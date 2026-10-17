import { build } from 'esbuild';
import { Miniflare } from 'miniflare';
import assert from 'node:assert/strict';
import { fetch as sourceFetch, EnvHttpProxyAgent } from 'undici';
const worker = await build({ stdin: { contents: "import {GET} from './app/api/spiele/route.ts';export default {fetch:GET};", resolveDir: process.cwd(), sourcefile: 'check.ts' }, write: false, bundle: true, format: 'esm', platform: 'browser' });
const proxy = new EnvHttpProxyAgent();
const runtime = new Miniflare({ modules: true, script: worker.outputFiles[0].text, compatibilityDate: '2026-05-15', outboundService: async request => {
  const upstream = await sourceFetch(request.url, { dispatcher: proxy, redirect: 'manual', headers: Object.fromEntries(request.headers) });
  return new Response(await upstream.arrayBuffer(), { status: upstream.status, headers: Object.fromEntries(upstream.headers) });
} });
try {
  const response = await runtime.dispatchFetch('https://test.local/api/spiele'+(process.argv[2]?'?date='+encodeURIComponent(process.argv[2]):''));
  const result = await response.json();
  assert.equal(response.status, 200, JSON.stringify(result));
  console.log(JSON.stringify({ status: response.status, from: result.from, to: result.to, count: result.matches.length, omittedRows: result.omittedRows, scores: result.matches.filter(m=>m.result).map(m=>({home:m.home,away:m.away,result:m.result})), unavailable:result.matches.filter(m=>m.resultUnavailable).length, categories: [...new Set(result.matches.map(m => m.category))] }));
} finally { await runtime.dispose(); await proxy.close(); }
