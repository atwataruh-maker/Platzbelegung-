import {useEffect,useMemo,useState,type ReactNode} from 'react';
import {CalendarPlus,ChevronLeft,ChevronRight,ExternalLink,Mail,MapPin,Phone,RefreshCw} from 'lucide-react';
import {appPath} from '@/frontend/api';
import {ClubHeader} from '@/components/club-header';
import {usePublicData} from '@/hooks/use-public-data';
import {GROUPS,DAY_NAMES,DAY_SHORT,activeCourses,hasCalendar,upcoming,berlinDate,berlinMinutes,courseFor,defaultDay,euro,ics,initials,mailHref,safeClubLink,seats,sessionsFor,shortDate,telHref,timing,weekDays,weekday,
 type BreitensportData,type Session,type Upcoming,type Course,type Entry,type Group,type Offer,type Person} from '@/lib/breitensport';
import clubLogo from '@/frontend/assets/vfl-logo.png';
import '../home.css';
import './sport.css';

const WEBSITE='https://www.vfl-ramsdorf.de/breitensport';
const params=()=>new URLSearchParams(location.search);
const offerLink=(id:string)=>appPath('/breitensport')+'?angebot='+encodeURIComponent(id);

function useNow(){
 const [now,setNow]=useState(()=>new Date());
 useEffect(()=>{const tick=()=>setNow(new Date());const t=setInterval(tick,30000);document.addEventListener('visibilitychange',tick);return()=>{clearInterval(t);document.removeEventListener('visibilitychange',tick);};},[]);
 return now;
}
function Hero({view,children,intro}:{view:'plan'|'kurse';children?:ReactNode;intro:string}){
 return <section className="home-hero sport-hero"><img className="crest-watermark" src={clubLogo} alt="" aria-hidden="true"/><time>Breitensport</time><h1>Sport für alle</h1><p>{intro}</p>
  <nav className="sport-switch" aria-label="Breitensport-Bereiche"><a href={appPath('/breitensport')} aria-current={view==='plan'?'page':undefined}>Wochenplan</a><a href={appPath('/breitensport')+'?ansicht=kurse'} aria-current={view==='kurse'?'page':undefined}>Kurse</a></nav>
  {children}</section>;
}
function Status({loading,error,retry,label}:{loading:boolean;error:boolean;retry:()=>void;label:string}){
 if(loading)return <p className="home-message" role="status">{label} werden geladen …</p>;
 if(error)return <><p className="home-message" role="alert">{label} derzeit nicht verfügbar.</p><button type="button" className="sport-retry" onClick={retry}><RefreshCw size={16}/>Erneut laden</button></>;
 return null;
}
function PersonRow({person,role}:{person:Person;role?:string}){
 const mail=person.email?mailHref(person.email):undefined;
 return <div className="sport-person"><span className="sport-avatar" aria-hidden="true">{initials(person.name)}</span><span>{role&&<small>{role}</small>}<strong>{person.name}</strong>
  {(person.phone||mail)&&<span className="sport-person-links">{person.phone&&<a href={telHref(person.phone)}><Phone size={15}/>Anrufen</a>}{mail&&<a href={mail}><Mail size={15}/>E-Mail</a>}</span>}</span></div>;
}
function SeatBadge({course}:{course:Course}){const s=seats(course);return <span className={'sport-seats sport-seats-'+s.tone}>{s.label}</span>;}
function EntryRow({entry,date,today,now}:{entry:Entry;date:string;today:string;now:number}){
 const {offer,session,course}=entry,state=timing(session,date,today,now);
 return <a className={'sport-offer sport-offer-'+state} href={offerLink(offer.id)}>
  <span className="sport-time"><b>{session.start}</b><small>bis {session.end}</small></span>
  <span className="sport-offer-copy">{state==='now'&&<span className="sport-live">Läuft gerade</span>}<strong>{offer.title}</strong>
   <span className="sport-meta">{session.audience&&<span className={'sport-tag sport-tag-'+(session.course?'kurs':offer.groups[0])}>{session.course?'Kurs · ':''}{session.audience}</span>}{session.venue??'Ort beim Ansprechpartner erfragen'}</span>
   {course&&course.seatsLeft!==null&&course.seatsLeft<=3&&<SeatBadge course={course}/>}</span>
  <ChevronRight size={18} className="sport-chevron"/></a>;
}

function Plan({data,loading,error,retry}:{data:BreitensportData|null;loading:boolean;error:boolean;retry:()=>void}){
 const now=useNow(),today=berlinDate(now),days=weekDays(today);
 const [date,setDate]=useState(()=>defaultDay(today)),[group,setGroup]=useState<Group|'alle'>('alle');
 // Follow the calendar when the app stays open overnight.
 useEffect(()=>{if(!weekDays(today).includes(date))setDate(defaultDay(today));},[today]);
 const entries=useMemo(()=>sessionsFor(data,date,group),[data,date,group]);
 const next=useMemo(()=>upcoming(data,today).filter(i=>group==='alle'||i.offer.groups.includes(group)),[data,today,group]);
 const label=`${DAY_NAMES[weekday(date)]}, ${shortDate(date)}`;
 return <><Hero view="plan" intro="Kurse, Kinder-, Senioren- und Mannschaftssport außerhalb des Fußballs.">
   <div className="sport-days" role="group" aria-label="Wochentag wählen">{days.map(d=><button type="button" key={d} aria-pressed={d===date} onClick={()=>setDate(d)}>{DAY_SHORT[weekday(d)]}<small>{shortDate(d)}</small></button>)}</div></Hero>
  <div className="home-sections">
   <section className="daily-panel sport-filter" aria-label="Zielgruppe"><div className="sport-chips">{GROUPS.map(g=><button type="button" key={g.id} aria-pressed={group===g.id} onClick={()=>setGroup(g.id)}>{g.label}</button>)}</div></section>
   {next.length>0&&<section aria-labelledby="sport-next"><div className="section-heading"><h2 id="sport-next">Nächste Termine</h2></div>{next.map(i=><UpcomingRow key={i.offer.id+i.date+i.start} item={i}/>)}</section>}
   <section aria-labelledby="sport-day"><div className="section-heading"><h2 id="sport-day">{label}</h2>{data&&<span>{entries.length} {entries.length===1?'Angebot':'Angebote'}</span>}</div>
    <Status loading={loading&&!data} error={error&&!data} retry={retry} label="Breitensport-Angebote"/>
    {data&&!entries.length&&<p className="home-message">{group==='alle'?'An diesem Tag gibt es keine Breitensport-Angebote.':'An diesem Tag gibt es für diese Zielgruppe keine Angebote.'}</p>}
    {entries.map((e,i)=><EntryRow key={e.offer.id+i} entry={e} date={date} today={today} now={berlinMinutes(now)}/>)}
    {date===today&&entries.length>0&&<p className="sport-legend">Grau: bereits vorbei · Blau umrandet: läuft gerade</p>}</section>
   {data&&<section className="sport-panel" aria-labelledby="sport-contacts"><h2 id="sport-contacts">Ansprechpartner</h2>{data.contacts.map(c=><PersonRow key={c.name} person={c} role={c.role}/>)}</section>}
   {data&&<p className="sport-source">Angebote laut Vereinswebsite, Stand {new Date(data.checkedAt+'T12:00:00Z').toLocaleDateString('de-DE')}. Ferien und Ausfälle bitte bei der Übungsleitung erfragen.</p>}
   <a className="home-website" href={WEBSITE} target="_blank" rel="noopener noreferrer">Breitensport auf der Vereinswebsite<ExternalLink size={17}/></a>
  </div></>;
}

function CourseCard({course}:{course:Course}){
 const s=seats(course),booking=course.bookingUrl?safeClubLink(course.bookingUrl):null;
 const when=course.teaser.filter(t=>/(montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag)s?/i.test(t)).slice(0,1)[0]??course.teaser[0]??'';
 return <article className="sport-course">{course.image&&<img src={safeClubLink(course.image)} alt="" loading="lazy" referrerPolicy="no-referrer"/>}<div>
  <SeatBadge course={course}/><h3>{course.title}</h3><p>{[when,course.venue].filter(Boolean).join(' · ')}</p>
  <dl className="sport-facts"><div><dt>{course.ongoing?'Seit':'Start'}</dt><dd>{course.startDate?shortDate(course.startDate):'–'}</dd></div><div><dt>{course.ongoing?'Laufend':'Ende'}</dt><dd>{course.ongoing?'fortl.':course.endDate?shortDate(course.endDate):'–'}</dd></div><div><dt>Termine</dt><dd>{course.units??'–'}</dd></div></dl>
  <p className="sport-price"><span>Mitglieder <b>{euro(course.priceMember)}</b></span><span>Nicht-Mitglieder <b>{euro(course.priceGuest)}</b></span></p>
  {s.tone==='full'
   ?<a className="sport-button sport-button-muted" href="mailto:kurs@breitensport.vfl-ramsdorf.de">Ausgebucht · Kursverwaltung anschreiben</a>
   :<a className="sport-button" href={booking??safeClubLink(course.url)} target="_blank" rel="noopener noreferrer">{booking?'Kurs buchen':'Zum Kurs'}<ExternalLink size={16}/></a>}
 </div></article>;
}
function Courses({data,loading,error,retry}:{data:BreitensportData|null;loading:boolean;error:boolean;retry:()=>void}){
 const today=berlinDate(useNow()),feed=data?.courses,list=activeCourses(feed,today);
 const stamp=feed?.retrievedAt?new Intl.DateTimeFormat('de-DE',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit',timeZone:'Europe/Berlin'}).format(new Date(feed.retrievedAt)):'';
 return <><Hero view="kurse" intro="Freie Kursplätze werden laufend von der Vereinswebsite übernommen."/>
  <div className="home-sections"><section aria-labelledby="sport-courses" className="sport-first"><div className="section-heading"><h2 id="sport-courses">Buchbare Kurse</h2>{stamp&&<span>{feed?.stale?'Letzter Stand ':'Stand '}{stamp} Uhr</span>}</div>
   <Status loading={loading&&!data} error={error&&!data} retry={retry} label="Kurse"/>
   {data&&!feed&&<p className="home-message" role="alert">Die Kursdaten der Vereinswebsite sind derzeit nicht erreichbar. Freie Plätze und Buchung bitte direkt auf der Website prüfen.</p>}
   {feed?.stale&&<p className="home-message">Die Vereinswebsite antwortet gerade nicht. Angezeigt wird der zuletzt geladene Stand.</p>}
   {feed&&!list.length&&<p className="home-message">Derzeit sind keine Kurse ausgeschrieben.</p>}
   {list.map(c=><CourseCard key={c.id} course={c}/>)}
   {list.length>0&&<p className="sport-source">Gebucht wird auf der Vereinswebsite. Fragen zur Buchung: Kursverwaltung.</p>}</section>
   <a className="home-website" href={WEBSITE+'/kurse'} target="_blank" rel="noopener noreferrer">Kurse auf der Vereinswebsite<ExternalLink size={17}/></a></div></>;
}

const longDate=(date:string)=>new Intl.DateTimeFormat('de-DE',{weekday:'long',day:'numeric',month:'long',year:'numeric',timeZone:'UTC'}).format(new Date(date+'T12:00:00Z'));
function DateList({session,today}:{session:Session;today:string}){
 return <span className="sport-dates" aria-label="Termine">{session.dates!.map(d=><span key={d} className={d<today?'sport-past':undefined}>{shortDate(d)}</span>)}</span>;
}
function UpcomingRow({item}:{item:Upcoming}){
 return <a className="sport-offer sport-upcoming" href={offerLink(item.offer.id)}><span className="sport-time"><b>{DAY_SHORT[weekday(item.date)]} {shortDate(item.date)}</b><small>{item.end?`${item.start}–${item.end}`:`${item.start} Uhr`}</small></span>
  <span className="sport-offer-copy"><strong>{item.title}</strong><span className="sport-meta">{item.venue??item.offer.title}</span></span><ChevronRight size={18} className="sport-chevron"/></a>;
}
/** "Donnerstag 16:30, 18:00, 19:00 Uhr · Freitag 16:30 Uhr" */
function leaderTimes(offer:Offer,name:string){
 const byDay=new Map<number,string[]>();for(const s of offer.sessions)if(s.leader===name&&!s.dates)byDay.set(s.day,[...(byDay.get(s.day)??[]),s.start]);
 return [...byDay].sort((a,b)=>((a[0]+6)%7)-((b[0]+6)%7)).map(([d,t])=>`${DAY_NAMES[d]} ${[...new Set(t)].sort().join(', ')} Uhr`).join(' · ')||(offer.sessions.some(s=>s.leader===name&&s.dates)?'Infos und Anmeldung':undefined);
}
function Detail({id,data,loading,error,retry}:{id:string;data:BreitensportData|null;loading:boolean;error:boolean;retry:()=>void}){
 const today=berlinDate(useNow());const offer:Offer|undefined=data?.offers.find(o=>o.id===id);
 const back=<a className="sport-back" href={appPath('/breitensport')}><ChevronLeft size={17}/>Breitensport</a>;
 if(!offer)return <><section className="home-hero sport-hero"><img className="crest-watermark" src={clubLogo} alt="" aria-hidden="true"/>{back}<h1>Angebot</h1></section><div className="home-sections"><section className="sport-first">
  <Status loading={loading&&!data} error={error&&!data} retry={retry} label="Breitensport-Angebote"/>{data&&<p className="home-message">Dieses Angebot gibt es nicht mehr. <a href={appPath('/breitensport')}>Zum Wochenplan</a></p>}</section></div></>;
 const venues=[...new Set(offer.sessions.map(s=>s.venue).filter((v):v is string=>Boolean(v)))];
 const download=()=>{const blob=new Blob([ics(offer,today,data?.courses)],{type:'text/calendar;charset=utf-8'});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download=offer.id+'.ics';document.body.append(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);};
 const sorted=[...offer.sessions].sort((a,b)=>((a.day+6)%7)-((b.day+6)%7)||a.start.localeCompare(b.start));
 const allDated=sorted.length>0&&sorted.every(s=>s.dates),datesAhead=sorted.some(s=>s.dates?.some(d=>d>=today));
 const events=[...(offer.events??[])].sort((a,b)=>a.date.localeCompare(b.date));
 return <><section className="home-hero sport-hero"><img className="crest-watermark" src={clubLogo} alt="" aria-hidden="true"/>{back}<time>{offer.area}</time><h1>{offer.title}</h1><p>{offer.intro}</p></section>
  <div className="home-sections">
   <section className="sport-panel" aria-labelledby="sport-times"><h2 id="sport-times">{allDated?'Termine':'Trainingszeiten'}</h2>{sorted.map((s,i)=>{const course=courseFor(data?.courses,s.course);return <div className="sport-session" key={i}>
    <b>{DAY_NAMES[s.day]} · {s.start}–{s.end} Uhr</b>{s.audience&&<span className="sport-age">{s.audience}</span>}
    <span>{[s.venue??'Ort beim Ansprechpartner erfragen',s.leader].filter(Boolean).join(' · ')}</span>{s.dates&&<DateList session={s} today={today}/>}{s.note&&<span className="sport-note">{s.note}</span>}{course&&<span className="sport-session-course"><SeatBadge course={course}/><a href={safeClubLink(course.bookingUrl??course.url)} target="_blank" rel="noopener noreferrer">Kurs auf der Website<ExternalLink size={13}/></a></span>}</div>;})}
    {allDated&&!datesAhead&&<p className="sport-dates-over">Alle Termine {sorted[0]?.dates?.[0]?.slice(0,4)} sind vorbei. Neue Termine folgen auf der Vereinswebsite.</p>}</section>
   {events.length>0&&<section className="sport-panel" aria-labelledby="sport-events"><h2 id="sport-events">Veranstaltungen</h2>{events.map(e=><div className={'sport-session'+(e.date<today?' sport-past':'')} key={e.date+e.title}><b>{e.title}</b><span>{longDate(e.date)} · ab {e.start} Uhr{e.venue?' · '+e.venue:''}</span>{e.note&&<span>{e.note}</span>}</div>)}</section>}
   {offer.leaders.length>0&&<section className="sport-panel" aria-labelledby="sport-leaders"><h2 id="sport-leaders">Übungsleitung</h2>{offer.leaders.map(p=><PersonRow key={p.name} person={p} role={leaderTimes(offer,p.name)}/>)}</section>}
   {offer.leaders.length===0&&data&&<section className="sport-panel" aria-labelledby="sport-leaders"><h2 id="sport-leaders">Ansprechpartner</h2>{data.contacts.slice(0,1).map(c=><PersonRow key={c.name} person={c} role={c.role}/>)}</section>}
   {venues.length>0&&<section className="sport-panel" aria-labelledby="sport-venue"><h2 id="sport-venue">Ort</h2>{venues.map(v=><p className="sport-venue" key={v}><MapPin size={18}/>{v}, Ramsdorf</p>)}</section>}
   <div className="sport-actions">{hasCalendar(offer,today)&&<button type="button" className="sport-button sport-button-ghost" onClick={download}><CalendarPlus size={17}/>Im Kalender</button>}<a className="sport-button sport-button-ghost" href={safeClubLink(offer.url)} target="_blank" rel="noopener noreferrer">Website<ExternalLink size={16}/></a></div>
  </div></>;
}

export default function Breitensport(){
 const {data,loading,error,retry}=usePublicData<BreitensportData>('/api/breitensport');
 const q=params(),id=q.get('angebot'),view=q.get('ansicht');
 useEffect(()=>{const offer=id&&data?.offers.find(o=>o.id===id);document.title=(offer?offer.title+' · ':view==='kurse'?'Kurse · ':'')+'Breitensport · VfL Ramsdorf';},[id,view,data]);
 const props={data,loading,error,retry};
 return <div className="home-shell sport-shell"><ClubHeader/><main className="home-main">{id?<Detail id={id} {...props}/>:view==='kurse'?<Courses {...props}/>:<Plan {...props}/>}</main></div>;
}
