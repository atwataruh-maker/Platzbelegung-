import { matchSource, parseMatches, sevenDays, isMatchDate, type MatchSchedule } from '@/lib/matches';
import { loadScoreFonts } from '@/lib/score-font';
const cached = new Map<string, MatchSchedule>();
const pending = new Map<string, Promise<MatchSchedule>>();
const FRESH = 5 * 60 * 1000;
const MAX_STALE = 24 * 60 * 60 * 1000;
export async function GET(request: Request) {
  const selected = new URL(request.url).searchParams.get('date');
  if (selected !== null && !isMatchDate(selected)) return Response.json({error:'Bitte ein gültiges Datum auswählen.'},{status:400});
  const { from, to } = selected ? {from:selected,to:selected} : sevenDays();
  const key = `${from}/${to}`;
  const previous = cached.get(key);
  const respond = (data: MatchSchedule) => Response.json(data, { headers: { 'Cache-Control': 'no-store' } });
  if (previous && Date.now() - Date.parse(previous.retrievedAt) < FRESH) return respond(previous);
  try {
    let work = pending.get(key);
    if (!work) {
      if (pending.size >= 32) return Response.json({error:'Bitte den Abruf in einem Moment erneut versuchen.'},{status:503});
      work = (async () => {
        const response = await fetch(matchSource(from, to), { redirect: 'manual', signal: AbortSignal.timeout(15000), headers: { Accept: 'text/html' } });
        if (!response.ok) throw new Error(`Source HTTP ${response.status}`);
        const html = await response.text();
        const parsed = parseMatches(html, from, to, await loadScoreFonts(html));
        return { ...parsed, from, to, retrievedAt: new Date().toISOString(), stale: false };
      })();
      pending.set(key, work);
      // Only the creator owns cleanup; a late waiter must not delete a newer request.
      void work.finally(() => { if (pending.get(key) === work) pending.delete(key); }).catch(() => {});
    }
    const data = await work;
    cached.delete(key); cached.set(key, data);
    while(cached.size > 32) cached.delete(cached.keys().next().value!);
    return respond(data);
  } catch (error) {
    console.error('Match schedule unavailable:', error instanceof Error ? error.message : 'Unknown error');
    if (previous && Date.now() - Date.parse(previous.retrievedAt) < MAX_STALE) return respond({ ...previous, stale: true });
    return Response.json({ error: 'Die Spiele konnten gerade nicht abgerufen werden. Bitte versuche es erneut oder öffne FUSSBALL.DE.' }, { status: 502, headers: { 'Cache-Control': 'no-store' } });
  }
}
