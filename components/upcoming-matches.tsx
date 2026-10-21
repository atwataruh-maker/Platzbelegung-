import {apiFetch as fetch,appPath} from '@/frontend/api';
'use client';
import { useEffect, useState } from 'react';
import { CalendarDays, MapPin, RefreshCw, ChevronDown, Shield, ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleTrigger, CollapsibleContent } from '@/components/ui/collapsible';
import { CLUB_URL, sevenDays, shiftMatchDate, isMatchDate, type Match, type MatchSchedule } from '@/lib/matches';
import './upcoming-matches.css';
const dateLabel = (date: string) => new Intl.DateTimeFormat('de-DE', { day: '2-digit', month: '2-digit', timeZone: 'Europe/Berlin' }).format(new Date(date + 'T12:00:00Z'));
function TeamLogo({ src }: { src?: string }) {
  const [failed, setFailed] = useState(false);
  useEffect(() => setFailed(false), [src]);
  return <span className="fixture-logo">{src && !failed ? <img src={src} alt="" width="40" height="40" loading="lazy" referrerPolicy="no-referrer" onError={() => setFailed(true)}/> : <Shield size={28} aria-hidden="true"/>}</span>;
}
function teamLabel(match: Match) {
  const clubName = match.location === 'Auswärts' ? match.away : match.home;
  const suffix = clubName.match(/Ramsdorf\s+(III|II|IV|I|\d+)(?:\b|$)/i)?.[1];
  const number = suffix ? ({ I: '1', II: '2', III: '3', IV: '4' }[suffix] ?? suffix) : '';
  if (match.location === 'Vereinsduell') return { title: 'VfL', subtitle: 'Duell', label: match.category };
  if (match.category === 'Herren') return { title: `${number || '1'}.`, subtitle: 'Herren', label: `${number || '1'}. Herrenmannschaft` };
  if (match.category === 'Frauen') return { title: number ? `F${number}` : 'F', subtitle: 'Frauen', label: 'Frauen' };
  const youth = match.category.match(/^([A-G])-Junior(en|innen)$/);
  if (youth) return { title: youth[1] + number, subtitle: youth[2] === 'innen' ? 'Mädchen' : 'Jugend', label: `${match.category}${number ? ` ${number}` : ''}` };
  return { title: 'VfL', subtitle: 'Team', label: match.category };
}
function FixtureCard({ match }: { match: Match }) {
  const label = teamLabel(match);
  return <Collapsible asChild><li className="fixture-card">
    <CollapsibleTrigger className="fixture-trigger" aria-label={`${label.label}: ${match.home} gegen ${match.away}, ${match.result ? `${match.result.home} zu ${match.result.away}, ${match.result.confirmed ? 'bestätigtes Ergebnis' : 'gemeldeter Spielstand'}` : `${match.time} Uhr`}. Details anzeigen`}>
      <span className="fixture-label" title={label.label}><strong>{label.title}</strong><small>{label.subtitle}</small></span>
      <span className="fixture-team"><TeamLogo src={match.homeLogo}/><span>{match.home}</span></span>
      <span className="fixture-kickoff">{match.result ? <><strong className="fixture-result">{match.result.home}:{match.result.away}</strong><span>{match.result.confirmed ? 'Endstand' : 'Gemeldet'}</span></> : <time dateTime={/^\d{2}:\d{2}$/.test(match.time) ? `${match.date}T${match.time}` : match.date}>{match.time}</time>}<span>{match.location === 'Vereinsduell' ? 'Vereinsduell' : match.location}</span>{match.status && <strong className="fixture-status">{match.status}</strong>}<ChevronDown size={14} className="fixture-chevron" aria-hidden="true"/></span>
      <span className="fixture-team"><TeamLogo src={match.awayLogo}/><span>{match.away}</span></span>
    </CollapsibleTrigger>
    <CollapsibleContent className="fixture-details"><p>{match.category} · {match.competition}</p><p>Anstoß: {match.time}{match.result ? ` · ${match.result.confirmed ? 'Bestätigtes Ergebnis' : 'Gemeldeter Spielstand'}: ${match.result.home}:${match.result.away}` : match.status ? ` · ${match.status}` : match.resultUnavailable ? ' · Ergebnisabruf nicht verfügbar' : ' · Kein Ergebnis verfügbar'}</p>{match.venue && <p className="fixture-venue"><MapPin size={15}/>{match.venue}</p>}<a href={match.url} target="_blank" rel="noopener noreferrer">Spieldetails auf FUSSBALL.DE</a></CollapsibleContent>
  </li></Collapsible>;
}
export function UpcomingMatches({selectedDate: externalDate, onDateChange, refreshKey = 0, onRefresh}: {selectedDate?: string | null; onDateChange?: (date: string | null) => void; refreshKey?: number; onRefresh?: () => void} = {}) {
  const [data, setData] = useState<MatchSchedule | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [reload, setReload] = useState(0);
  const [showAll, setShowAll] = useState(false);
  const [localDate, setLocalDate] = useState<string | null>(null);
  const selectedDate = externalDate === undefined ? localDate : externalDate;
  const setSelectedDate = (date: string | null) => { setLocalDate(date); onDateChange?.(date); };
  const today = sevenDays().from;
  const expected = selectedDate ? {from:selectedDate,to:selectedDate} : sevenDays();
  const visibleData = data?.from === expected.from && data.to === expected.to ? data : null;
  function selectDay(day: string | null) { if(day === selectedDate) return; setData(null); setError(''); setShowAll(false); setSelectedDate(day); }

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true); setError('');
    (async () => {
      try {
        const response = await fetch('/api/spiele' + (selectedDate ? '?date=' + selectedDate : ''), { cache: 'no-store', signal: controller.signal });
        if (!response.ok) throw new Error('Die Spiele sind gerade nicht erreichbar.');
        const result = await response.json() as MatchSchedule;
        if (!controller.signal.aborted) setData(result);
      } catch { if (!controller.signal.aborted) setError('Die Spiele sind gerade nicht erreichbar. Bitte erneut laden oder bei FUSSBALL.DE nachsehen.'); }
      finally { if (!controller.signal.aborted) setLoading(false); }
    })();
    const interval = setInterval(() => setReload(v => v + 1), 5 * 60 * 1000);
    return () => { controller.abort(); clearInterval(interval); };
  }, [reload, selectedDate, refreshKey]);
  const shown = visibleData?.matches.slice(0, 5) ?? [];
  const remaining = visibleData?.matches.slice(5) ?? [];
  function groups(matches: Match[]) {
    return [...new Set(matches.map(m => m.date))].map(date => <div className="match-day" key={date}><h3>{new Intl.DateTimeFormat('de-DE', { weekday: 'short', day: '2-digit', month: '2-digit', timeZone: 'Europe/Berlin' }).format(new Date(date + 'T12:00:00Z'))}</h3><ul>{matches.filter(m => m.date === date).map(match => <FixtureCard match={match} key={match.id}/>)}</ul></div>);
  }
  return <section className="upcoming" aria-labelledby="upcoming-title">
    <div className="upcoming-header"><div><p className="upcoming-kicker"><CalendarDays size={15}/> Spiele & Ergebnisse</p><h2 id="upcoming-title">{selectedDate ? (selectedDate === today ? 'Heute' : dateLabel(selectedDate)) : 'Die nächsten 7 Tage'}</h2><p className="upcoming-subtitle">{visibleData ? `${dateLabel(visibleData.from)}${visibleData.from === visibleData.to ? '' : '–' + dateLabel(visibleData.to)} · ${visibleData.matches.length} Spiele` : 'Alle Mannschaften · Heim & Auswärts'}</p></div><Button variant="outline" size="icon" disabled={loading} onClick={() => onRefresh ? onRefresh() : setReload(v => v + 1)} aria-label="Spiele aktualisieren"><RefreshCw size={18} className={loading ? 'spin' : ''}/></Button></div>
    <div className="match-date-navigation" aria-label="Spieltag auswählen">
      <Button variant="outline" size="icon" onClick={() => selectDay(shiftMatchDate(selectedDate || today,-1))} aria-label="Vorheriger Tag"><ChevronLeft size={18}/></Button>
      <input type="date" aria-label="Datum des Spieltags" value={selectedDate || today} min="2000-01-01" max="2100-12-31" onChange={e=>{if(isMatchDate(e.target.value))selectDay(e.target.value);}}/>
      <Button variant="outline" size="icon" onClick={() => selectDay(shiftMatchDate(selectedDate || today,1))} aria-label="Nächster Tag"><ChevronRight size={18}/></Button>
      <Button variant={selectedDate === today ? 'default' : 'outline'} onClick={() => selectDay(today)}>Heute</Button>
      <Button variant={!selectedDate ? 'default' : 'outline'} onClick={() => selectDay(null)}>7 Tage</Button>
    </div>
    {!!visibleData?.omittedRows && <p className="match-warning">{visibleData.omittedRows} Einträge der Quelle konnten nicht gelesen werden. Die übrigen Spiele werden angezeigt.</p>}
    {visibleData?.matches.some(m=>m.resultUnavailable) && <p className="match-warning">Einige Spielstände sind gerade nicht lesbar. Bitte erneut laden oder das Spiel bei FUSSBALL.DE öffnen.</p>}
    {loading && !visibleData && <p className="match-message" role="status">Spiele werden von FUSSBALL.DE geladen …</p>}
    {error && <div className="match-warning" role="alert">{error} <Button variant="ghost" onClick={() => onRefresh ? onRefresh() : setReload(v => v + 1)} disabled={loading}>Erneut laden</Button></div>}
    {visibleData && (visibleData.stale || error) && <p className="match-warning">Letzter verfügbarer Stand – Änderungen sind möglicherweise noch nicht enthalten.</p>}
    {visibleData && visibleData.matches.length === 0 && !error && <p className="match-message">Für diesen Zeitraum sind bei FUSSBALL.DE keine Spiele eingetragen.</p>}
    {groups(shown)}
    {remaining.length > 0 && <Collapsible open={showAll} onOpenChange={setShowAll}><CollapsibleContent>{groups(remaining)}</CollapsibleContent><CollapsibleTrigger asChild><Button variant="outline" className="fixtures-more">{showAll ? 'Weniger Spiele anzeigen' : `Alle ${visibleData!.matches.length} Spiele anzeigen`}<ChevronDown size={16} className={showAll ? 'is-open' : ''}/></Button></CollapsibleTrigger></Collapsible>}
    <div className="upcoming-source"><a href={CLUB_URL} target="_blank" rel="noopener noreferrer">Quelle: FUSSBALL.DE</a>{visibleData && <span>Stand {new Date(visibleData.retrievedAt).toLocaleString('de-DE', { timeZone: 'Europe/Berlin', day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })} Uhr</span>}</div>
    <p className="match-footnote">{visibleData?.provisional ? 'Der Quellplan enthält auch vorläufige Ansetzungen. ' : ''}Spielfreie Termine werden ausgeblendet. Kurzfristige Änderungen bitte beim Team prüfen.</p>
  </section>;
}
