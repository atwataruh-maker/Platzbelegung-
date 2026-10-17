export const CLUB_ID = '00ES8GN8QS00004CVV0AG08LVUPGND5I';
export const CLUB_URL = `https://www.fussball.de/verein/vfl-ramsdorf-westfalen/-/id/${CLUB_ID}`;
export type Match = { id: string; date: string; time: string; category: string; competition: string; home: string; away: string; homeLogo?: string; awayLogo?: string; venue: string; location: 'Heim' | 'Auswärts' | 'Vereinsduell'; status: string; url: string; result?: { home: number; away: number; confirmed: boolean }; resultUnavailable?: boolean };
export type MatchSchedule = { from: string; to: string; matches: Match[]; retrievedAt: string; provisional: boolean; stale: boolean; omittedRows?: number };
export function sevenDays(now = new Date()) {
  const from = new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Berlin', year: 'numeric', month: '2-digit', day: '2-digit' }).format(now);
  const end = new Date(from + 'T12:00:00Z'); end.setUTCDate(end.getUTCDate() + 6);
  return { from, to: end.toISOString().slice(0, 10) };
}
export function shiftMatchDate(date: string, days: number) {
  const value = new Date(date + 'T12:00:00Z'); value.setUTCDate(value.getUTCDate() + days);
  return value.toISOString().slice(0, 10);
}
export function isMatchDate(value: string) {
  if (!/^(20\d{2}|2100)-\d{2}-\d{2}$/.test(value)) return false;
  const parsed = new Date(value + 'T12:00:00Z');
  return Number.isFinite(parsed.getTime()) && parsed.toISOString().slice(0,10) === value;
}
export function matchSource(from: string, to: string) {
  return `https://www.fussball.de/ajax.club.matchplan/-/datum-bis/${to}/datum-von/${from}/id/${CLUB_ID}/match-type/-1/max/999/mode/PAGE/show-venues/true`;
}
function plain(html: string) {
  const entities: Record<string, string> = { amp: '&', quot: '"', apos: "'", lt: '<', gt: '>', nbsp: ' ', auml: 'ä', ouml: 'ö', uuml: 'ü', Auml: 'Ä', Ouml: 'Ö', Uuml: 'Ü', szlig: 'ß' };
  return html.replace(/<[^>]*>/g, ' ').replace(/&(#x[0-9a-f]+|#\d+|[a-z]+);/gi, (whole, key: string) => {
    if (key.startsWith('#')) { const n = key[1].toLowerCase() === 'x' ? parseInt(key.slice(2), 16) : Number(key.slice(1)); return n > 0 && n <= 0x10ffff ? String.fromCodePoint(n) : ''; }
    return entities[key] ?? whole;
  }).replace(/[\u200b-\u200d\ufeff]/g, '').replace(/\s+/g, ' ').trim();
}
// The public PAGE view contains readable dates; do not use the obfuscated PRINT view.
export function parseMatches(html: string, from: string, to: string, fonts: Record<string, Record<string, string>> = {}): Pick<MatchSchedule, 'matches' | 'provisional' | 'omittedRows'> {
  if (!html.includes('club-matchplan-table') || html.length > 2000000) throw new Error('Unrecognized match schedule');
  if (html.includes('ajax.club.matchplan.loadmore')) throw new Error('Incomplete match schedule');
  const table = html.match(/<tbody\b[^>]*>([\s\S]*?)<\/tbody>/i)?.[1];
  if (!table) {
    // FUSSBALL.DE omits tbody entirely for an empty day, retaining the table header.
    if (/<table\b[^>]*>\s*<thead\b[^>]*>[\s\S]*?<\/thead>\s*<\/table>/i.test(html)) return {matches:[],provisional:html.includes('hint-pre-publish'),omittedRows:0};
    throw new Error('Missing match table');
  }
  const chunks = table.split(/<tr\b[^>]*class="[^"]*\brow-headline\b[^"]*"[^>]*>/i).slice(1);
  if (!chunks.length && !/keine\s+(?:spiele|begegnungen)|keine.*gefunden/i.test(plain(table))) throw new Error('Missing match rows');
  let omittedRows = 0;
  const matches = chunks.flatMap(chunk => {
    // A bye is not a fixture: the source deliberately has no opponent or match link.
    if (/<td\b[^>]*class="[^"]*\bcolumn-club\b[^"]*"[^>]*>\s*<span[^>]*>\s*spielfrei\s*<\/span>/i.test(chunk)) return [];
    try {
    const headline = plain(chunk.split('</tr>')[0]);
    const dateParts = headline.match(/(\d{2})\.(\d{2})\.(\d{4})\s*-\s*(.*?)\s*Uhr\s*\|\s*([^|]+)\|\s*(.*)/);
    const clubs = [...chunk.matchAll(/<td\b[^>]*class="[^"]*\bcolumn-club\b[^"]*"[^>]*>([\s\S]*?)<\/td>/gi)].map(m => {
      const ours = m[1].includes(CLUB_ID);
      const logo = m[1].match(/data-responsive-image="((?:https:)?\/\/www\.fussball\.de\/export\.media\/-\/action\/getLogo\/format\/\d+\/id\/[A-Z0-9]+)"/)?.[1];
      return { name: plain(m[1].match(/<div\b[^>]*class="club-name"[^>]*>([\s\S]*?)<\/div>/i)?.[1] ?? ''), ours, logo: ours ? 'https://www.vfl-ramsdorf.de/img/VfLRamsdorf.png' : logo?.startsWith('//') ? `https:${logo}` : logo };
    });
    const url = chunk.match(/href="(https:\/\/www\.fussball\.de\/(?:spiel|spieltag)\/[^"<>]+)"/)?.[1];
    if (!dateParts || clubs.length !== 2 || clubs.some(c => !c.name) || !url) throw new Error('Invalid match row');
    const [, day, month, year, rawTime, category, competition] = dateParts;
    const date = `${year}-${month}-${day}`;
    if (new Date(date + 'T12:00:00Z').toISOString().slice(0,10) !== date) throw new Error('Invalid match date');
    const time = /^\d{2}:\d{2}$/.test(rawTime) ? rawTime : 'Zeit offen';
    const scoreHtml = chunk.match(/<td\b[^>]*class="column-score"[^>]*>([\s\S]*?)<\/td>/i)?.[1] ?? '';
    const score = plain(scoreHtml);
    const readScore = (side: string) => {
      const span = scoreHtml.match(new RegExp('<span\\b([^>]*class="score-' + side + '"[^>]*)>([\\s\\S]*?)<\\/span>', 'i'));
      if (!span) return undefined;
      const font = span[1].match(/data-obfuscation="([a-z0-9]+)"/)?.[1];
      const raw = plain(span[2]);
      return font ? [...raw].map(c => fonts[font]?.[c] ?? (/^[0-9-]$/.test(c) ? c : '?')).join('') : raw;
    };
    const homeScore = readScore('left'), awayScore = readScore('right');
    const moved = score.match(/^(\d{2}\.\d{2}\.\d{4})\s+(\d{2}:\d{2})$/);
    const status = /\bo\.E\./i.test(score) ? 'Ohne Ergebniswertung' : moved ? `Verlegt: ${moved[1]} ${moved[2]}` : score.match(/Ausfall|Absetzung|Abbruch|verlegt|Nichtantritt(?:\s+(?:HEIM|GAST|BEIDE))?|Spielfrei/i)?.[0] ?? '';
    const result = !status && homeScore !== undefined && awayScore !== undefined && /^\d{1,3}$/.test(homeScore) && /^\d{1,3}$/.test(awayScore) ? { home: Number(homeScore), away: Number(awayScore), confirmed: scoreHtml.includes('icon-verified') } : undefined;
    const resultUnavailable = !status && Boolean(homeScore?.includes('?') || awayScore?.includes('?'));
    const venue = plain(chunk.match(/<tr\b[^>]*class="[^"]*\brow-venue\b[^"]*"[^>]*>([\s\S]*?)<\/tr>/i)?.[1] ?? '').replace(/^Spielstätte:\s*/, '');
    return { id: url.includes('/spieltag/') ? `${url.split('/').pop()}-${date}-${time}-${clubs.map(c => c.name).join('-')}` : url.split('/').pop()!, date, time, category: category.trim(), competition: competition.trim(), home: clubs[0].name, away: clubs[1].name, homeLogo: clubs[0].logo, awayLogo: clubs[1].logo, venue, location: clubs.every(c => c.ours) ? 'Vereinsduell' : clubs[0].ours ? 'Heim' : 'Auswärts', status, url: plain(url), result, resultUnavailable } as Match;
    } catch { omittedRows++; return []; }
  });
  if (omittedRows && !matches.length) throw new Error('No readable match rows');
  return { omittedRows, matches: [...new Map(matches.filter(m => m.date >= from && m.date <= to).map(m => [m.id, m])).values()].sort((a,b) => `${a.date} ${a.time}`.localeCompare(`${b.date} ${b.time}`)), provisional: html.includes('hint-pre-publish') };
}
