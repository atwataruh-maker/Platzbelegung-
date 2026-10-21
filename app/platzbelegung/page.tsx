import {apiFetch as fetch,appPath} from '@/frontend/api';
'use client';
import {useEffect,useState,type CSSProperties} from 'react';
import {ArrowLeft,ArrowRight,CalendarDays,Check,ExternalLink,RefreshCw,AlertTriangle,LoaderCircle,MapPin,ArrowRightLeft,Shield} from 'lucide-react';
import {Button} from '@/components/ui/button';
import {UpcomingMatches} from '@/components/upcoming-matches';
import {sevenDays,shiftMatchDate,isMatchDate} from '@/lib/matches';
import {SOURCE,PLAN_LABEL} from '@/lib/plan';
import {clock,fieldLabel,type Field} from '@/lib/training-plan';
import type {Allocation,Booking} from '@/lib/pitch-allocation';
import {laneEntries,turfLayout} from '@/lib/pitch-layout';
type SourceState={state:'verified';origin:'upload';checkedAt:string};
import './pitch.css';
type DayPlan=Allocation&{source:SourceState;season:string;matchesRetrievedAt:string|null};
const label=(date:string)=>new Intl.DateTimeFormat('de-DE',{weekday:'long',day:'2-digit',month:'long',timeZone:'Europe/Berlin'}).format(new Date(date+'T12:00:00Z'));
const fields:{title:string;className:string;lanes:{title:string;fields:Field[]}[]}[]=[
 {title:'Kunstrasen',className:'turf',lanes:[{title:'KuRa 1 · Vorne',fields:['k1a','k1b']},{title:'KuRa 2 · Hinten',fields:['k2a','k2b']}]},
 {title:'Trainingsrasen',className:'grass',lanes:[{title:'1/3 · Großspielform',fields:['r1']},{title:'2/3',fields:['r2']},{title:'3/3',fields:['r3']},{title:'Reserve-Ecke',fields:['corner']}]},
 {title:'Hauptplatz',className:'main-pitch',lanes:[{title:'Hälfte 1',fields:['main1']},{title:'Hälfte 2',fields:['main2']}]}];
function PitchTeamLogo({src}:{src?:string}){
 const [failed,setFailed]=useState(false);
 useEffect(()=>setFailed(false),[src]);
 return <span className="pitch-team-logo">{src&&!failed?<img src={src} alt="" width="22" height="22" loading="lazy" referrerPolicy="no-referrer" onError={()=>setFailed(true)}/>:<Shield size={16} aria-hidden="true"/>}</span>;
}
function BookingCard({booking,style,wide=false}:{booking:Booking;style?:CSSProperties;wide?:boolean}){
 const min=Math.min(...booking.segments.map(s=>s.start)),max=Math.max(...booking.segments.map(s=>s.end));
 return <li style={style} data-wide={wide||undefined} className={'pitch-booking '+booking.kind+(booking.moved?' relocated':'')}>
  <div className="booking-top"><time>{clock(min)}–{clock(max)}</time><span>{booking.kind==='match'?(wide?'Spiel · beide Hälften':'Spiel'):booking.kind==='reserve'?'Reserve':booking.moved?'Verschoben':'Training'}</span></div>
  {booking.kind==='match'&&booking.teams?<div className="pitch-match-teams" aria-label={booking.title}>
   <div><PitchTeamLogo src={booking.teams.homeLogo}/><strong>{booking.teams.home}</strong></div><span className="pitch-versus" aria-hidden="true">vs.</span><div><PitchTeamLogo src={booking.teams.awayLogo}/><strong>{booking.teams.away}</strong></div>
  </div>:<strong>{booking.title}</strong>}
  {booking.kind==='match'&&<p>Anstoß {booking.kickoff} · inkl. Zeitpuffer</p>}
  {booking.kind==='reserve'&&<p>Für zusätzliche Heimspiele vorgesehen</p>}
  {booking.segments.map((s,i)=><p key={i} className="booking-area">{booking.segments.length>1?`${clock(s.start)}–${clock(s.end)} · `:''}{fieldLabel(s.fields)}</p>)}
  {booking.moved&&<p className="booking-change"><ArrowRightLeft size={13}/> Zuvor: {[...new Set(booking.original?.map(s=>fieldLabel(s.fields)))].join(' / ')}</p>}
  {booking.url&&<a href={booking.url} target="_blank" rel="noopener noreferrer">Spieldetails <ExternalLink size={12}/></a>}
 </li>;
}
export default function PitchPage(){
 const [date,setDate]=useState<string|null>(()=>sevenDays().from);
 const [reload,setReload]=useState(0),[data,setData]=useState<DayPlan|null>(null),[loading,setLoading]=useState(true),[error,setError]=useState('');
 const today=sevenDays().from,day=date??today;
 const current=data?.date===day?data:null;
 useEffect(()=>{
  const controller=new AbortController();setLoading(true);setError('');setData(null);
  (async()=>{try{
   const response=await fetch('/api/belegung?date='+day,{cache:'no-store',signal:controller.signal});
   if(!response.ok)throw new Error('Belegung konnte nicht geladen werden.');
   const result=await response.json() as DayPlan;
   if(!controller.signal.aborted)setData(result);
  }catch{if(!controller.signal.aborted)setError('Die Belegung ist gerade nicht erreichbar. Bitte erneut laden.');}
  finally{if(!controller.signal.aborted)setLoading(false);}})();
  const interval=setInterval(()=>setReload(v=>v+1),300000);
  return()=>{controller.abort();clearInterval(interval);};
 },[day,reload]);
 const select=(value:string|null)=>{if(value!==date){setDate(value);if((value??today)!==day){setData(null);setError('');}}};
 const weekStart=shiftMatchDate(day,-((new Date(day+'T12:00:00Z').getUTCDay()+6)%7));
 const trainings=current?.bookings.filter(b=>b.kind==='training')??[];
 return <div className="app-shell pitch-app"><header className="club-header"><div className="header-inner"><a className="club-brand" href={appPath('/')} aria-label="Zur Startseite"><img className="club-logo" src="https://www.vfl-ramsdorf.de/img/VfLRamsdorf.png" alt="VfL Ramsdorf"/><span className="club-name">RAMSDORF<small>Vereins-App</small></span></a><div className="header-actions"><span className="preview-badge">Version 0.10</span><Button variant="ghost" size="icon" className="refresh" onClick={()=>setReload(v=>v+1)} disabled={loading} aria-label="Belegung und Spiele aktualisieren"><RefreshCw className={loading?'spin':''}/></Button></div></div></header>
 <main>
  <a href={appPath('/')} className="pitch-back"><ArrowLeft size={15}/> Übersicht</a>
  <div className="page-heading"><div><p className="eyebrow">Fußball / Plätze & Training</p><h1>Platzbelegung</h1><p className="intro">Ein Tag. Alle Plätze. Änderungen im Blick.</p></div><span className="heading-symbol"><MapPin size={26}/></span></div>
  <div className="allocation-banner" aria-live="polite" aria-atomic="true">
   {loading&&<div className="pitch-notice neutral"><LoaderCircle className="spin" size={20}/><div><strong>Belegung wird abgeglichen</strong><p>Trainingsplan und Heimspiele für {label(day)}</p></div></div>}
   {error&&<div className="pitch-notice danger" role="alert"><AlertTriangle size={20}/><div><strong>Abgleich nicht möglich</strong><p>{error}</p><Button variant="outline" onClick={()=>setReload(v=>v+1)}>Erneut laden</Button></div></div>}
   {current&&<>
    {current.changes.length>0&&<div className="pitch-notice changed"><ArrowRightLeft size={22}/><div><strong>{current.changes.length===1?'Training verschoben':`${current.changes.length} Trainings verschoben`} · {label(day)}</strong><ul>{current.changes.map(c=><li key={c.team}><b>{c.team} · {c.time}</b><span>{c.from} <ArrowRight size={13}/> {c.to}</span><small>{c.reason}</small></li>)}</ul></div></div>}
    {current.conflicts.length>0&&<div className="pitch-notice danger"><AlertTriangle size={22}/><div><strong>Abstimmung nötig · {label(day)}</strong><ul>{current.conflicts.map(c=><li key={c}>{c}</li>)}</ul></div></div>}
    {current.warnings.length>0&&<div className="pitch-notice warning"><AlertTriangle size={20}/><div><strong>{current.automatic?'Hinweise zum Abgleich':'Automatik eingeschränkt'}</strong>{current.warnings.map(w=><p key={w}>{w}</p>)}</div></div>}
    {current.automatic&&!current.changes.length&&!current.conflicts.length&&<div className="pitch-notice success"><Check size={20}/><div><strong>Keine Verschiebung erforderlich</strong><p>{label(day)} · Im hinterlegten Plan keine Überschneidung mit Heimspielen.</p></div></div>}
   </>}
  </div>
  <UpcomingMatches selectedDate={date} onDateChange={select} refreshKey={reload} onRefresh={()=>setReload(v=>v+1)}/>
  <section className="pitch-section" aria-labelledby="pitch-title">
   <div className="pitch-section-heading"><div><p className="eyebrow">Dein Tagesplan</p><h2 id="pitch-title">{label(day)}</h2></div><span>{trainings.length} Trainings</span></div>
   <div className="pitch-date-bar"><Button variant="outline" size="icon" aria-label="Belegung vorheriger Tag" onClick={()=>select(shiftMatchDate(day,-1))}><ArrowLeft size={17}/></Button><label><CalendarDays size={17}/><input type="date" min="2000-01-01" max="2100-12-31" aria-label="Datum der Platzbelegung" value={day} onChange={e=>{if(isMatchDate(e.target.value))select(e.target.value);}}/></label><Button variant="outline" size="icon" aria-label="Belegung nächster Tag" onClick={()=>select(shiftMatchDate(day,1))}><ArrowRight size={17}/></Button><Button variant="outline" onClick={()=>select(today)}>Heute</Button></div>
   <div className="pitch-week" aria-label="Tag dieser Woche">{Array.from({length:7},(_,i)=>{const d=shiftMatchDate(weekStart,i);return <button key={d} onClick={()=>select(d)} aria-pressed={d===day} className={d===day?'active':''}><span>{['Mo','Di','Mi','Do','Fr','Sa','So'][i]}</span><strong>{Number(d.slice(-2))}</strong></button>;})}</div>
   <div className="pitch-source-note current"><Check size={17}/><p><strong>{PLAN_LABEL}</strong><span>Grundlage: der bereitgestellte Belegungsplan. Heimspiele und daraus entstehende Platzwechsel werden automatisch ergänzt.</span></p><a href={appPath(SOURCE)} target="_blank" rel="noopener noreferrer" aria-label="Original-PDF öffnen"><ExternalLink size={18}/></a></div>
   {current&&!current.automatic&&<p className="pitch-paused">Grundplan ohne automatische Verschiebung. Gezeigte Spiele und Trainings können sich überschneiden; Flächen sind nicht als frei bestätigt.</p>}
   {loading&&<div className="pitch-skeleton" role="status">Platzübersicht wird geladen …</div>}
   {current&&<><div className="pitch-legend"><span className="legend-training">Training</span><span className="legend-match">Spiel</span><span className="legend-moved">Verschoben</span><span className="legend-reserve">Spielreserve</span></div>
   {fields.map(group=><section key={group.title} className={'pitch-ground '+group.className}><div className="pitch-ground-title"><span className="pitch-mini" aria-hidden="true"/><h3>{group.title}</h3><small>{group.className==='main-pitch'?'2 Hälften · Ausweichtraining':group.className==='turf'?'2 Hälften':'3 Bereiche + Reserve'}</small></div>{group.className!=='grass'?<div className="turf-board"><div className="turf-headings">{group.lanes.map(lane=><h4 key={lane.title}>{lane.title}</h4>)}</div>{turfLayout(current.bookings,[group.lanes[0].fields,group.lanes[1].fields]).length?<ul className="turf-bookings">{turfLayout(current.bookings,[group.lanes[0].fields,group.lanes[1].fields]).map(entry=><BookingCard key={entry.key} booking={entry.booking} wide={entry.span===2} style={{gridColumn:`${entry.column} / span ${entry.span}`,gridRow:entry.row}}/>)}</ul>:<p className="pitch-empty">Keine Belegung im hinterlegten Plan</p>}</div>:<div className="pitch-lanes">{group.lanes.map(lane=>{const entries=laneEntries(current.bookings,lane.fields);return <div className="pitch-lane" key={lane.title}><h4>{lane.title}</h4>{entries.length?<ul>{entries.map(b=><BookingCard key={b.id} booking={b}/>)}</ul>:<p className="pitch-empty">Keine Belegung im hinterlegten Plan</p>}</div>;})}</div>}</section>)}
   {!!trainings.filter(b=>!b.segments.length).length&&<div className="pitch-unassigned"><h3>Noch ohne Ausweichfläche</h3>{trainings.filter(b=>!b.segments.length).map(b=><p key={b.id}><strong>{b.title}</strong> · {b.original&&clock(b.original[0].start)} – bitte mit der Platzkoordination abstimmen.</p>)}</div>}
   <p className="pitch-updated">Plan übernommen: {new Date(current.source.checkedAt).toLocaleString('de-DE',{timeZone:'Europe/Berlin'})}{current.matchesRetrievedAt?' · Spiele: '+new Date(current.matchesRetrievedAt).toLocaleTimeString('de-DE',{timeZone:'Europe/Berlin',hour:'2-digit',minute:'2-digit'}):''} · Automatischer Abgleich alle 5 Minuten bei geöffneter App.</p>
   </>}
  </section>
  <details className="pitch-rules"><summary>So werden die Plätze verteilt</summary><div><p><strong>Spiele haben Vorrang.</strong> G–E-Jugend: eine Kunstrasenhälfte. D–A-Jugend: beide Hälften. 1.–3. Herren: Hauptplatz. Andere bekannte Erwachsenenteams: kompletter Kunstrasen. Auswärtsspiele und abgesagte Spiele belegen keinen Platz.</p><p><strong>Trainingszeiten bleiben gleich.</strong> Zuerst bleibt jede konfliktfreie Einheit auf ihrer Fläche. Betroffene Teams wechseln für die gesamte Einheit. Kleinfeldteams können auf einen Rasenteil oder die Reserve-Ecke ausweichen. Größere Teams bekommen die Großspielform auf Rasen 1/3, Rasen 2/3 und 3/3 zusammen oder eine Kunstrasenhälfte. Für verschobenes Training wird kein kompletter Kunstrasen vergeben. Die 1. Herren und alle Jugendmannschaften können als letzte Option eine Hauptplatzhälfte nutzen; dort sind maximal zwei Trainings gleichzeitig möglich. Spiele blockieren beide Hauptplatzhälften. Die Raseneinteilung folgt weiterhin der PDF.</p><p><strong>Zeitannahmen für Spiele:</strong> 30 Minuten vorher zum Aufwärmen, 15 Minuten Halbzeit und 15 Minuten anschließend. Angenommene Spielzeit: G 60, F 40, E 50, D 60, C 70, B 80, A und Erwachsene 90 Minuten. Das sind Planungsvorgaben; Verlängerungen und tatsächliche Endzeiten sind nicht bekannt. Spielfeste und fehlende Zeiten erfordern Abstimmung.</p><p><strong>Quelle & Saison:</strong> Der bereitgestellte Sommerplan ersetzt den früheren Website-Plan. Er wiederholt sich wöchentlich bis zum nächsten Planwechsel; ein Winterplan ist nicht hinterlegt. SF Nordvelen ist entsprechend „ab Okt.“ ab Oktober 2026 berücksichtigt. Gemeinsame Gruppen und Spielreserven sind aus der PDF übernommen. Ferien, Platzsperren und kurzfristige Trainingsausfälle sind darin nicht abgebildet.</p><p>Bei einem neuen Saisonplan muss die neue PDF übernommen werden. Wenn Spieldaten fehlen, unvollständig oder veraltet sind, pausiert die automatische Verschiebung. Hinweise erscheinen im Banner. Vorläufige Ansetzungen werden mit Hinweis vorsorglich eingeplant. Die Berechnung gilt für diese App; sie ändert keine Vereins- oder FUSSBALL.DE-Buchung.</p><a href={appPath(SOURCE)} target="_blank" rel="noopener noreferrer">Originalplan ansehen <ExternalLink size={14}/></a></div></details>
  <footer className="footer"><span>VfL Ramsdorf · Seit 1936</span><a href="https://www.vfl-ramsdorf.de/" target="_blank" rel="noopener noreferrer">Vereinswebsite <ExternalLink size={13}/></a></footer>
 </main></div>;
}
