import {useEffect,useState} from 'react';
import {CalendarDays,DoorOpen,ExternalLink,ChevronRight,RefreshCw,Goal} from 'lucide-react';
import {appPath} from '@/frontend/api';
import {ClubHeader} from '@/components/club-header';
import {usePublicData} from '@/hooks/use-public-data';
import {todayCards,type TodayData} from '@/lib/home-today';
import {sevenDays,type Match} from '@/lib/matches';
import {recentMatches,scoreLabel,incomplete,teamKey,safeMatchLink,type RecentSchedule} from '@/lib/home-results';
import clubLogo from '@/frontend/assets/vfl-logo.png';
import './home.css';
function MatchRow({match:m,recent=false}:{match:Match;recent?:boolean}){
 const label=scoreLabel(m),date=m.date.split('-').slice(1).reverse().join('.');
 return <a className="match-row" href={safeMatchLink(m.url)} target="_blank" rel="noopener noreferrer">
  <span className="match-symbol"><Goal size={24}/></span><span className="match-copy"><small>{recent?date+' · ':''}{m.category} · {m.time}{recent?'': ' Uhr · '+m.location}</small><strong>{m.home} – {m.away}</strong>{(recent||m.status||m.result)&&<small className="match-status">{label}</small>}</span>
  {m.result&&!m.status?<strong className="match-score" aria-label={`Spielstand ${m.result.home} zu ${m.result.away}`}>{m.result.home} : {m.result.away}</strong>:<ChevronRight size={18} className="match-chevron"/>}
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
  <section className="home-hero"><img className="crest-watermark" src={clubLogo} alt="" aria-hidden="true"/><time dateTime={day}>{dateLabel}</time><h1>Heute beim VfL</h1><label htmlFor="home-team">Meine Mannschaft</label><select id="home-team" value={selected} onChange={e=>setSelected(e.target.value)}><option value="">Alle Mannschaften</option>{selected&&!options.some(([key])=>key===selected)&&<option value={selected}>{selected.replace('|',' · ')}</option>}{options.map(([key,label])=><option key={key} value={key}>{label}</option>)}</select><p className="selector-hint">Filter für heutige Spiele und letzte Ergebnisse</p></section>
  <div className="home-sections"><section className="daily-panel" aria-labelledby="important-title"><h2 id="important-title">Heute wichtig</h2>{cards.map(c=><a className={'daily-row'+(c.warning?' daily-warning':'')} key={c.id} href={appPath(c.link)}><span className="daily-icon">{c.id==='pitch'?<CalendarDays size={24}/>:<DoorOpen size={24}/>}</span><span><small>{c.label} · Alle Mannschaften</small><strong>{c.title}</strong>{c.lines.map((line,i)=><p key={i}>{line}</p>)}{c.detail&&c.title!=='Belegung noch vorläufig'&&<small>{c.detail}</small>}</span><ChevronRight size={18}/></a>)}{today.error&&<button className="source-retry" onClick={today.retry}><RefreshCw size={14}/>Erneut laden</button>}</section>
  <section aria-labelledby="games-title"><div className="section-heading"><h2 id="games-title">Heutige Spiele</h2><span>{games.filter(filter).length}</span></div>{today.loading&&!today.data?<p className="home-message" role="status">Spiele werden geladen …</p>:<>{sourceProblem&&<p className="source-warning" role="status">Spielabgleich unvollständig. Weitere Spiele oder Änderungen sind möglich.</p>}{games.filter(filter).map(m=><MatchRow key={m.id} match={m}/>)}{!games.filter(filter).length&&<p className="home-message">{sourceProblem?'Heutige Spiele derzeit nicht vollständig verfügbar.':selected?'Heute keine Spiele für diese Mannschaft.':'Heute sind keine Spiele angesetzt.'}</p>}</>}</section>
  <section aria-labelledby="results-title"><div className="section-heading"><h2 id="results-title">Ergebnisse</h2><span>Letzte 48 Stunden</span></div><p className="section-caption">Nach Anstoßzeit · neueste zuerst</p>{recent.loading&&!recent.data?<p className="home-message" role="status">Ergebnisse werden geladen …</p>:<>{(recent.error||incomplete(recent.data))&&<p className="source-warning" role="status">{recent.error||recent.data?.stale?'Ergebnisse konnten nicht aktualisiert werden. Angezeigte Spielstände können veraltet sein.':'Die Quelle enthält vorläufige oder unvollständige Angaben. Der Status steht beim jeweiligen Spiel.'}</p>}{results.filter(filter).map(m=><MatchRow key={m.id} match={m} recent/>)}{!results.filter(filter).length&&<p className="home-message">{recent.error||incomplete(recent.data)?'Ergebnisse derzeit nicht verfügbar.':selected?'Keine Spiele dieser Mannschaft in den letzten 48 Stunden.':'Keine Spielergebnisse der letzten 48 Stunden vorhanden.'}</p>}</>}{recent.error&&<button className="source-retry" onClick={recent.retry}><RefreshCw size={14}/>Erneut laden</button>}</section>
  <a className="home-website" href="https://www.vfl-ramsdorf.de/" target="_blank" rel="noopener noreferrer">Zur Vereinswebsite<ExternalLink size={17}/></a></div>
 </main></div>;
}
