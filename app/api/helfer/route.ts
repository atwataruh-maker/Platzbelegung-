import { helperStore } from '@/lib/helper-store';
import { createRequestSchema, changeStatusSchema } from '@/lib/helper-contract';
import { canManageHelpers, isSameOriginMutation } from '@/lib/helper-auth';

const json = (data: unknown, status = 200) => Response.json(data, { status, headers: { 'Cache-Control': 'no-store' } });
const fail = (error: unknown) => {
  const message = error instanceof Error ? error.message : '';
  if (message === 'NOT_FOUND') return json({ error: 'Das Gesuch wurde nicht gefunden.' }, 404);
  if (message.endsWith('_CONFLICT')) return json({ error: 'Das Gesuch wurde zwischenzeitlich geändert. Bitte aktualisiere die Liste.' }, 409);
  console.error('Helper requests unavailable:', message);
  return json({ error: 'Speichern oder Laden ist gerade nicht möglich. Deine Eingaben bleiben erhalten. Bitte erneut versuchen.' }, 503);
};
export async function GET(request: Request) {
  try {
    const canManage = canManageHelpers(request.headers);
    const store = helperStore();
    const records = await store.list();
    return json({ records: records.map(({ created_by, ...rest }) => rest), canManage });
  } catch (error) { return fail(error); }
}
async function write(request: Request, action: 'create' | 'status') {
  if (!canManageHelpers(request.headers)) return json({ error: 'Nur der Betreiber kann Gesuche verwalten.' }, 403);
  if (!isSameOriginMutation(request)) return json({ error: 'Ungültige Anfrage.' }, 403);
  let body: unknown;
  try {
    const text = await request.text();
    if (text.length > 16000) return json({ error: 'Die Eingaben sind zu lang.' }, 413);
    body = JSON.parse(text);
  } catch { return json({ error: 'Die Eingaben konnten nicht gelesen werden.' }, 400); }
  try {
    let record;
    if (action === 'create') {
      const parsed = createRequestSchema.safeParse(body);
      if (!parsed.success) return json({ error: parsed.error.issues[0].message }, 400);
      record = await helperStore().create(parsed.data, request.headers.get('oai-authenticated-user-id')!);
    } else {
      const parsed = changeStatusSchema.safeParse(body);
      if (!parsed.success) return json({ error: 'Bitte einen gültigen Status auswählen.' }, 400);
      record = await helperStore().changeStatus(parsed.data.id, parsed.data.expectedStatus, parsed.data.status);
    }
    const { created_by, ...publicRecord } = record;
    return json({ record: publicRecord }, action === 'create' ? 201 : 200);
  } catch (error) { return fail(error); }
}
export const POST = (request: Request) => write(request, 'create');
export const PATCH = (request: Request) => write(request, 'status');
