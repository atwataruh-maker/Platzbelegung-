// Breitensport: maintained weekly offers (server/breitensport.json) combined with live course data
// from the club website. Times are local Europe/Berlin wall-clock times.
export type Group = 'kinder' | 'erwachsene' | 'senioren';
export type Person = { name: string; phone?: string; email?: string };
export type Contact = Person & { role: string };
/** Weekly session; with `dates` it only takes place on those dates (e.g. Sportabzeichen-Abnahme). */
export type Session = { day: number; start: string; end: string; venue?: string; audience?: string; leader?: string; months?: number[]; dates?: string[]; note?: string; course?: string };
/** One-off event without weekly rhythm (e.g. Übergabefeier). */
export type ClubEvent = { date: string; start: string; end?: string; title: string; venue?: string; note?: string };
export type Offer = { id: string; title: string; area: string; groups: Group[]; intro: string; url: string; leaders: Person[]; sessions: Session[]; events?: ClubEvent[] };
export type Course = { id: number; title: string; url: string; image: string | null; teaser: string[]; leader: string | null; startDate: string | null; endDate: string | null; ongoing: boolean;
  units: number | null; venue: string | null; priceMember: number | null; priceGuest: number | null; seatsLeft: number | null; bookingUrl: string | null; detailsMissing?: boolean };
export type CourseFeed = { courses: Course[]; retrievedAt: string; stale?: boolean; source: string };
export type BreitensportData = { checkedAt: string; source: string; note?: string; contacts: Contact[]; offers: Offer[]; courses: CourseFeed | null };
export type Entry = { offer: Offer; session: Session; course?: Course };

export const GROUPS: { id: Group | 'alle'; label: string }[] = [
  { id: 'alle', label: 'Alle' }, { id: 'kinder', label: 'Kinder' }, { id: 'erwachsene', label: 'Erwachsene' }, { id: 'senioren', label: 'Senior*innen' },
];
export const DAY_NAMES = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
export const DAY_SHORT = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];

export const berlinDate = (now = new Date()) => new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Berlin', year: 'numeric', month: '2-digit', day: '2-digit' }).format(now);
export const berlinMinutes = (now = new Date()) => {
  const [h, m] = new Intl.DateTimeFormat('en-GB', { timeZone: 'Europe/Berlin', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).format(now).split(':').map(Number);
  return h * 60 + m;
};
export const minutes = (time: string) => { const [h, m] = time.split(':').map(Number); return h * 60 + m; };
const utc = (date: string) => new Date(date + 'T12:00:00Z');
export const weekday = (date: string) => utc(date).getUTCDay();
export const addDays = (date: string, days: number) => { const d = utc(date); d.setUTCDate(d.getUTCDate() + days); return d.toISOString().slice(0, 10); };
export const shortDate = (date: string) => date.slice(8, 10) + '.' + date.slice(5, 7) + '.';

/** Monday to Saturday of the current week; on Sundays the coming week. */
export function weekDays(today: string): string[] {
  const day = weekday(today), monday = addDays(today, day === 0 ? 1 : 1 - day);
  return [0, 1, 2, 3, 4, 5].map(i => addDays(monday, i));
}
/** Today when it has a weekday column (Mo–Sa), otherwise the coming Monday. */
export const defaultDay = (today: string) => weekday(today) === 0 ? addDays(today, 1) : today;

const norm = (value: string) => value.toLowerCase().normalize('NFKD').replace(/[^a-z0-9]+/g, ' ').trim();
export function courseFor(feed: CourseFeed | null | undefined, title?: string): Course | undefined {
  if (!feed || !title) return undefined;
  return feed.courses.find(c => norm(c.title) === norm(title));
}
/**
 * Whether a course session takes place on a date. Without live course data the maintained plan is shown.
 * With live data a course must still be listed and the date must lie inside its period.
 */
export function courseRuns(feed: CourseFeed | null | undefined, title: string, date: string) {
  if (!feed) return true;
  const course = courseFor(feed, title);
  if (!course) return false;
  if (course.startDate && date < course.startDate) return false;
  if (!course.ongoing && course.endDate && date > course.endDate) return false;
  return true;
}
export function sessionsFor(data: BreitensportData | null | undefined, date: string, group: Group | 'alle' = 'alle'): Entry[] {
  if (!data) return [];
  const day = weekday(date), month = Number(date.slice(5, 7));
  return data.offers.filter(o => group === 'alle' || o.groups.includes(group)).flatMap(offer => offer.sessions
    .filter(s => s.day === day && (!s.months || s.months.includes(month)) && (!s.dates || s.dates.includes(date)) && (!s.course || courseRuns(data.courses, s.course, date)))
    .map(session => ({ offer, session, course: courseFor(data.courses, session.course) })))
    .sort((a, b) => a.session.start.localeCompare(b.session.start) || a.session.end.localeCompare(b.session.end) || a.offer.title.localeCompare(b.offer.title, 'de'));
}
export type Upcoming = { offer: Offer; date: string; start: string; end?: string; title: string; venue?: string; note?: string; kind: 'event' | 'termin' };
/** Dated appointments and events from today on (until `days` ahead), earliest first. */
export function upcoming(data: BreitensportData | null | undefined, today: string, days = 60, limit = 3): Upcoming[] {
  if (!data) return [];
  const until = addDays(today, days), items: Upcoming[] = [];
  for (const offer of data.offers) {
    for (const e of offer.events ?? []) items.push({ offer, ...e, kind: 'event' });
    for (const s of offer.sessions) for (const date of s.dates ?? []) items.push({ offer, date, start: s.start, end: s.end, title: offer.title + (s.audience ? ' · ' + s.audience : ''), venue: s.venue, kind: 'termin' });
  }
  return items.filter(i => i.date >= today && i.date <= until).sort((a, b) => a.date.localeCompare(b.date) || a.start.localeCompare(b.start)).slice(0, limit);
}
/** Weekly sessions repeat; dated sessions and events only count while they are still ahead. */
export const hasCalendar = (offer: Offer, today: string) =>
  offer.sessions.some(s => !s.dates || s.dates.some(d => d >= today)) || (offer.events ?? []).some(e => e.date >= today);
export type Timing = 'done' | 'now' | 'upcoming';
export function timing(session: Session, date: string, today: string, nowMinutes: number): Timing {
  if (date < today) return 'done';
  if (date > today) return 'upcoming';
  return nowMinutes >= minutes(session.end) ? 'done' : nowMinutes >= minutes(session.start) ? 'now' : 'upcoming';
}
export function seats(course: Course): { label: string; tone: 'full' | 'few' | 'ok' | 'unknown' } {
  const n = course.seatsLeft;
  if (n === null || n === undefined) return { label: 'Plätze auf der Website prüfen', tone: 'unknown' };
  if (n <= 0) return { label: 'Ausgebucht', tone: 'full' };
  if (n <= 3) return { label: n === 1 ? 'Nur noch 1 Platz' : `Nur noch ${n} Plätze`, tone: 'few' };
  return { label: `${n} Plätze frei`, tone: 'ok' };
}
export const euro = (value: number | null) => value === null ? '–' : new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR', maximumFractionDigits: value % 1 ? 2 : 0 }).format(value);
export const telHref = (phone: string) => 'tel:' + phone.replace(/[^+\d]/g, '');
export const mailHref = (email: string) => /^[^\s@<>"]+@[^\s@<>"]+\.[a-z]{2,}$/i.test(email) ? 'mailto:' + email : undefined;
/** Only links back to the club website leave the app. */
export const safeClubLink = (url: string | null | undefined) => url && /^https:\/\/www\.vfl-ramsdorf\.de\/[^\s"<>]*$/.test(url) ? url : 'https://www.vfl-ramsdorf.de/breitensport';
export const initials = (name: string) => name.replace(/^Großmeister\s+/, '').split(/\s+/).filter(Boolean).map(p => p[0]).slice(0, 2).join('').toUpperCase();
/** Courses that are not over yet, current ones first. */
export function activeCourses(feed: CourseFeed | null | undefined, today: string): Course[] {
  return (feed?.courses ?? []).filter(c => c.ongoing || !c.endDate || c.endDate >= today);
}

const icsText = (value: string) => value.replace(/\\/g, '\\\\').replace(/\n/g, '\\n').replace(/[,;]/g, m => '\\' + m);
const BYDAY = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
/** Calendar entries: weekly sessions from the next occurrence, upcoming dated sessions and events individually. */
export function ics(offer: Offer, today: string, feed?: CourseFeed | null): string {
  const lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//VfL Ramsdorf//Vereins-App//DE', 'CALSCALE:GREGORIAN',
    'BEGIN:VTIMEZONE', 'TZID:Europe/Berlin',
    'BEGIN:DAYLIGHT', 'TZOFFSETFROM:+0100', 'TZOFFSETTO:+0200', 'TZNAME:CEST', 'DTSTART:19700329T020000', 'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU', 'END:DAYLIGHT',
    'BEGIN:STANDARD', 'TZOFFSETFROM:+0200', 'TZOFFSETTO:+0100', 'TZNAME:CET', 'DTSTART:19701025T030000', 'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU', 'END:STANDARD',
    'END:VTIMEZONE'];
  const stamp = (date: string, time: string) => date.replace(/-/g, '') + 'T' + time.replace(':', '') + '00';
  const now = 'DTSTAMP:' + new Date().toISOString().replace(/[-:]/g, '').slice(0, 15) + 'Z';
  const single = (uid: string, date: string, start: string, end: string, summary: string, venue?: string, description?: string) => lines.push('BEGIN:VEVENT', `UID:${uid}@vfl-ramsdorf-app`, now,
    `DTSTART;TZID=Europe/Berlin:${stamp(date, start)}`, `DTEND;TZID=Europe/Berlin:${stamp(date, end)}`, 'SUMMARY:' + icsText(summary),
    ...(venue ? ['LOCATION:' + icsText(venue + ', Ramsdorf')] : []), ...(description ? ['DESCRIPTION:' + icsText(description)] : []), 'END:VEVENT');
  (offer.events ?? []).filter(e => e.date >= today).forEach((e, i) => {
    const end = e.end ?? `${String(Math.min(23, Number(e.start.slice(0, 2)) + 2)).padStart(2, '0')}:${e.start.slice(3)}`;
    single(`${offer.id}-event-${i}-${e.date}`, e.date, e.start, end, e.title + ' · VfL Ramsdorf', e.venue, [e.note ?? '', offer.url].filter(Boolean).join('\n'));
  });
  offer.sessions.forEach((s, i) => {
    const summary = `${offer.title}${s.audience ? ' (' + s.audience + ')' : ''} · VfL Ramsdorf`, description = [s.leader ? 'Übungsleitung: ' + s.leader : '', s.note ?? '', offer.url].filter(Boolean).join('\n');
    if (s.dates) { s.dates.filter(d => d >= today).forEach(d => single(`${offer.id}-${i}-${d}`, d, s.start, s.end, summary, s.venue, description)); return; }
    const course = courseFor(feed, s.course);
    let first = today;
    if (course?.startDate && course.startDate > first) first = course.startDate;
    while (weekday(first) !== s.day || (s.months && !s.months.includes(Number(first.slice(5, 7))))) first = addDays(first, 1);
    let rule = `RRULE:FREQ=WEEKLY;BYDAY=${BYDAY[s.day]}`;
    if (s.months) rule += ';BYMONTH=' + s.months.join(',');
    if (course && !course.ongoing && course.endDate) rule += ';UNTIL=' + course.endDate.replace(/-/g, '') + 'T235959Z';
    lines.push('BEGIN:VEVENT', `UID:${offer.id}-${i}@vfl-ramsdorf-app`, now,
      `DTSTART;TZID=Europe/Berlin:${stamp(first, s.start)}`, `DTEND;TZID=Europe/Berlin:${stamp(first, s.end)}`, rule,
      'SUMMARY:' + icsText(summary), ...(s.venue ? ['LOCATION:' + icsText(s.venue + ', Ramsdorf')] : []), 'DESCRIPTION:' + icsText(description), 'END:VEVENT');
  });
  lines.push('END:VCALENDAR');
  return lines.join('\r\n') + '\r\n';
}
