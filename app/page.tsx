import {useEffect,useState} from 'react';
import {CalendarDays,DoorOpen,ExternalLink,ChevronRight,RefreshCw} from 'lucide-react';
import {appPath} from '@/frontend/api';
import {ClubHeader} from '@/components/club-header';
import {usePublicData} from '@/hooks/use-public-data';
import {todayCards,type TodayData} from '@/lib/home-today';
import {sevenDays,type Match} from '@/lib/matches';
import {recentMatches,incomplete,teamKey,safeMatchLink,type RecentSchedule} from '@/lib/home-results';
import clubLogo from '@/frontend/assets/vfl-logo.png';
import './home.css';
function TeamLogo({src,name}:{src?:string;name:string}){
 const [failed,setFailed]=useState(false);
 const url=/\bVfL Ramsdorf\b/i.test(name)?clubLogo:src;
 return url&&!failed
  ? <img className="match-team-logo" key={url} src={url} alt="" loading="lazy" referrerPolicy="no-referrer" onError={()=>setFailed(true)}/>
  : <span className="match-team-fallback" aria-hidden="true">{name.replace(/^(?:FC|SC|SV|DJK|JSG)\s+/i,'').slice(0,2).toUpperCase()}</span>;
}
function MatchRow({match:m,recent=false}:{match:Match;recent?:boolean}){
 const date=m.date.split('-').slice(1).reverse().join('.');
 const scoreUncertain=Boolean(m.resultStale||m.result&&!m.result.confirmed);
 return <a className="match-row" href={safeMatchLink(m.url)} target="_blank" rel="noopener noreferrer">
   <span className="match-copy">
     <small>{recent?date+' · ':''}{m.category} · {m.time}{recent?'':' Uhr · '+m.location}</small>
     <span className="match-teams">
       <span><TeamLogo key={m.homeLogo||m.home} src={m.homeLogo} name={m.home}/><strong>{m.home}</strong></span>
       <span><TeamLogo key={m.awayLogo||m.away} src={m.awayLogo} name={m.away}/><strong>{m.away}</strong></span>
     </span>
     {m.status&&<small className="match-status">{m.status}</small>}
   </span>
   {m.result&&!m.status
     ? <strong className="match-score" title={scoreUncertain?'Vorläufiger oder älterer Spielstand':undefined} aria-label={(scoreUncertain?'Vorläufiger oder älterer Spielstand: ':'Spielstand: ')+m.result.home+' zu '+m.result.away}>{scoreUncertain&&<span className="score-approx" aria-hidden="true">≈ </span>}{m.result.home} : {m.result.away}</strong>
     :recent&&!m.status?<span className="match-score" aria-label="Ergebnis noch nicht verfügbar">–</span>
     :<ChevronRight size={18} className="match-chevron"/>}
 </a>;
}
export default function Home(){
 const [now,setNow]=useState(()=>new Date()),[selected,setSelected]=useState('');
 useEffect(()=>{const tick=()=>setNow(new Date());const timer=setInterval(tick,30000);document.addEventListener('visibilitychange',tick);return()=>{clearInterval(timer);document.removeEventListener('visibilitychange',tick);};},[]);
 const day=sevenDays(now).from,today=usePublicData<TodayData>('/api/heute?date='+day),recent=usePublicData<RecentSchedule>('/api/ergebnisse');
 const cards=todayCards(day,today.data,today.loading,today.error).slice(1);
 const games=(today.data?.date===day?today.data?.schedule?.matches??[]:[]).filter(m=>m.date===day).sort((a,b)=>a.time.localeCompare(b.time));
 const results=recentMatches(recent.data,now);
 const options=[...new Map([...games,...results].map(m=>[teamKey(m),`${m.category} · ${/Ramsdorf/i.test(m.home)?m.home:m.away}`])).entries()].sort((a,b)=>a[1].localeCompare(b[1],'de'));
 const filter=(m:Match)=>!selected||teamKey(m)===selected;
 const sourceProblem=today.error||incomplete(today.data?.schedule);
 const dateLabel=new Intl.DateTimeFormat('de-DE',{weekday:'long',day:'numeric',month:'long',timeZone:'Europe/Berlin'}).format(now);
 return <div className="home-shell"><ClubHeader/><main className="home-main">
  <section className="home-hero"><img className="crest-watermark" src={clubLogo} alt="" aria-hidden="true"/><time dateTime={day}>{dateLabel}</time><h1>Heute beim VfL</h1><label htmlFor="home-team">Meine Mannschaft</label><select id="home-team" value={selected} onChange={e=>setSelected(e.target.value)}><option value="">Alle Mannschaften</option>{selected&&!options.some(([key])=>key===selected)&&<option value={selected}>{selected.replace('|',' · ')}</option>}{options.map(([key,label])=><option key={key} value={key}>{label}</option>)}</select></section>
  <div className="home-sections"><section className="daily-panel" aria-labelledby="important-title"><h2 id="important-title">Heute wichtig</h2>{cards.map(c=><a className={'daily-row'+(c.warning?' daily-warning':'')} key={c.id} href={appPath(c.link)}><span className="daily-icon">{c.id==='pitch'?<CalendarDays size={24}/>:<DoorOpen size={24}/>}</span><span><small>{c.label} · Alle Mannschaften</small><strong>{c.title}</strong>{c.lines.map((line,i)=><p key={i}>{line}</p>)}{c.detail&&c.title!=='Belegung noch vorläufig'&&<small>{c.detail}</small>}</span><ChevronRight size={18}/></a>)}{today.error&&<button className="source-retry" onClick={today.retry}><RefreshCw size={14}/>Erneut laden</button>}</section>
  <section aria-labelledby="games-title"><div className="section-heading"><h2 id="games-title">Heutige Spiele</h2><span>{games.filter(filter).length}</span></div>{today.loading&&!today.data?<p className="home-message" role="status">Spiele werden geladen …</p>:<>{games.filter(filter).map(m=><MatchRow key={m.id} match={m}/>)}{!games.filter(filter).length&&<p className="home-message">{sourceProblem?'Heutige Spiele derzeit nicht vollständig verfügbar.':selected?'Heute keine Spiele für diese Mannschaft.':'Heute sind keine Spiele angesetzt.'}</p>}</>}</section>
  <section aria-labelledby="results-title"><div className="section-heading"><h2 id="results-title">Ergebnisse</h2><span>Letzte 48 Stunden</span></div>{recent.loading&&!recent.data?<p className="home-message" role="status">Ergebnisse werden geladen …</p>:<>{results.filter(filter).map(m=><MatchRow key={m.id} match={m} recent/>)}{!results.filter(filter).length&&<p className="home-message">{recent.error||incomplete(recent.data)?'Ergebnisse derzeit nicht verfügbar.':selected?'Keine Spiele dieser Mannschaft in den letzten 48 Stunden.':'Keine Spielergebnisse der letzten 48 Stunden vorhanden.'}</p>}</>}{recent.error&&<button className="source-retry" onClick={recent.retry}><RefreshCw size={14}/>Erneut laden</button>}</section>
  <a className="home-website" href="https://www.vfl-ramsdorf.de/" target="_blank" rel="noopener noreferrer">Zur Vereinswebsite<ExternalLink size={17}/></a></div>
 </main></div>;
}
