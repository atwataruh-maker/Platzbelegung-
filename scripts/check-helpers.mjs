import assert from 'node:assert/strict';
import { readFile, mkdtemp } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { build } from 'esbuild';
import { Miniflare } from 'miniflare';

const built = await build({
  stdin: { contents: `import {GET,POST,PATCH} from './app/api/helfer/route.ts'; export default {fetch(request) { return ({GET,POST,PATCH})[request.method](request); } };`, resolveDir: process.cwd(), sourcefile: 'test-worker.ts' },
  bundle: true, write: false, format: 'esm', platform: 'browser', external: ['cloudflare:workers'],
});
const persistence = await mkdtemp(join(tmpdir(), 'vfl-helpers-test-'));
const options = { modules: true, script: built.outputFiles[0].text, compatibilityDate: '2026-01-01', d1Databases: { DB: 'helpers-test' }, d1Persist: persistence };
let mf = new Miniflare(options);
const admin = { 'oai-authenticated-user-id': 'test-owner', 'oai-authenticated-user-email': 'atwataruh@googlemail.com' };
const draft = { id: '4769b1f4-3a65-4ec6-8678-c76d2cfae0c4', title: "Fliesenleger '); DROP TABLE helper_requests;--", description: 'Acht Stunden Arbeit in den Umkleiden.', category: 'Handwerk', hours: 8, location: 'Umkleiden', timeframe: 'nach Absprache', contact: 'Vereinsbüro' };
async function call(method, body, identity = admin, extra = {}) {
  const response = await mf.dispatchFetch('https://app.test/api/helfer', { method, headers: { ...identity, ...extra, ...(body ? { 'content-type': 'application/json' } : {}) }, body: body ? JSON.stringify(body) : undefined });
  return { status: response.status, body: await response.json() };
}
try {
  const db = await mf.getD1Database('DB');
  const migration = await readFile(new URL('../drizzle/0000_amusing_retro_girl.sql', import.meta.url), 'utf8');
  await db.batch(migration.split('--> statement-breakpoint').map(sql => db.prepare(sql.trim())));
  assert.equal((await call('POST', draft, {})).status, 403);
  assert.equal((await call('POST', draft, { ...admin, 'oai-authenticated-user-email': 'viewer@example.com' })).status, 403);
  assert.equal((await call('POST', draft, admin, { origin: 'https://other.test' })).status, 403);
  assert.equal((await call('POST', { ...draft, title: '' })).status, 400);
  let result = await call('GET');
  assert.equal(result.body.canManage, true);
  assert.equal(result.body.records.length, 0);
  result = await call('POST', draft);
  assert.equal(result.status, 201);
  assert.equal(result.body.record.title, draft.title);
  assert.equal(result.body.record.status, 'open');
  assert.equal(result.body.record.created_by, undefined);
  assert.equal((await call('POST', draft)).status, 201);
  assert.equal((await call('POST', { ...draft, hours: 10 })).status, 409);
  assert.equal((await call('GET')).body.records.length, 1);
  result = await call('PATCH', { id: draft.id, expectedStatus: 'open', status: 'completed' });
  assert.equal(result.body.record.status, 'completed');
  assert.ok(result.body.record.completed_at);
  assert.equal((await call('PATCH', { id: draft.id, expectedStatus: 'open', status: 'completed' })).status, 200);
  await mf.dispose();
  mf = new Miniflare(options);
  result = await call('GET');
  assert.equal(result.body.records.find(r => r.id === draft.id).status, 'completed');
  result = await call('PATCH', { id: draft.id, expectedStatus: 'completed', status: 'open' });
  assert.equal(result.body.record.status, 'open');
  assert.equal(result.body.record.completed_at, null);
  assert.equal((await call('PATCH', { id: 'missing', expectedStatus: 'open', status: 'completed' })).status, 404);
  assert.equal((await call('PATCH', { id: draft.id, expectedStatus: 'open', status: 'completed' }, {})).status, 403);
  assert.equal((await call('GET', undefined, {})).body.canManage, false);
  console.log('PASS: create, validation, idempotency, completion, reopening, persisted restart, SQL safety and owner-only writes.');
} finally {
  await mf.dispose();
}
