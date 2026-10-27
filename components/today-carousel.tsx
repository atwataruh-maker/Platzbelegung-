import {useEffect,useRef,useState} from 'react';
import {ArrowRight,ChevronLeft,ChevronRight,Pause,Play,RefreshCw} from 'lucide-react';
import {apiFetch,appPath} from '@/frontend/api';
import {sevenDays} from '@/lib/matches';
import {todayCards,type TodayData} from '@/lib/home-today';
import gamesImage from '@/frontend/assets/banner-games.webp';
import pitchImage from '@/frontend/assets/banner-pitch.webp';
import cabinsImage from '@/frontend/assets/banner-cabins.webp';
const images={games:gamesImage,pitch:pitchImage,cabins:cabinsImage};
export function TodayCarousel(){
 const [day,setDay]=useState(()=>sevenDays().from),[data,setData]=useState<TodayData|null>(null),[loading,setLoading]=useState(true),[error,setError]=useState(false),[reload,setReload]=useState(0);
 const [active,setActive]=useState(0),[paused,setPaused]=useState(()=>window.matchMedia('(prefers-reduced-motion: reduce)').matches),[hidden,setHidden]=useState(document.hidden),[hover,setHover]=useState(false),[focus,setFocus]=useState(false);
 const touch=useRef<{x:number;y:number}|null>(null);
 useEffect(()=>{
  const check=()=>setDay(sevenDays().from);
  const visible=()=>{setHidden(document.hidden);if(!document.hidden){check();setReload(v=>v+1);}};
  const timer=setInterval(check,30000);document.addEventListener('visibilitychange',visible);
  const media=window.matchMedia('(prefers-reduced-motion: reduce)');const reduce=()=>{if(media.matches)setPaused(true);};media.addEventListener('change',reduce);
  return()=>{clearInterval(timer);document.removeEventListener('visibilitychange',visible);media.removeEventListener('change',reduce);};
 },[]);
 useEffect(()=>{
  if(hidden)return;
  const controller=new AbortController();let disposed=false;
  const timeout=setTimeout(()=>controller.abort(),45000);
  (async()=>{try{
   const response=await apiFetch('/api/heute?date='+day,{cache:'no-store',signal:controller.signal});
   if(!response.ok)throw Error('Today unavailable');
   const result=await response.json() as TodayData;
   if(result.date!==day||result.allocation?.date!==day||!Array.isArray(result.allocation?.changes)||!Array.isArray(result.allocation?.conflicts)||!Array.isArray(result.allocation?.cabins?.conflicts)||typeof result.allocation.automatic!=='boolean'||typeof result.allocation.cabins.confirmed!=='boolean'||(result.schedule&&!Array.isArray(result.schedule.matches)))throw Error('Invalid daily overview');
   if(!disposed){setData(result);setError(false);}
  }catch{if(!disposed)setError(true);}finally{clearTimeout(timeout);if(!disposed)setLoading(false);}})();
  const timer=setInterval(()=>setReload(v=>v+1),60000);
  return()=>{disposed=true;controller.abort();clearTimeout(timeout);clearInterval(timer);};
 },[day,reload,hidden]);
 const running=!paused&&!hidden&&!hover&&!focus;
 useEffect(()=>{if(!running)return;const timer=setInterval(()=>setActive(i=>(i+1)%3),5000);return()=>clearInterval(timer);},[running,active]);
 const cards=todayCards(day,data?.date===day?data:null,loading,error);
 const select=(i:number)=>setActive((i+3)%3);
 const dateLabel=new Intl.DateTimeFormat('de-DE',{weekday:'long',day:'numeric',month:'long',timeZone:'Europe/Berlin'}).format(new Date(day+'T12:00:00Z'));
 return <section className="today-section" aria-label="Heute beim VfL" aria-roledescription="Karussell" onMouseEnter={()=>setHover(true)} onMouseLeave={()=>setHover(false)} onFocusCapture={()=>setFocus(true)} onBlurCapture={e=>{if(!e.currentTarget.contains(e.relatedTarget as Node|null))setFocus(false);}}>
  <div className="today-heading"><div><p className="today-kicker">Dein Verein. Dein Tag.</p><h1>Heute beim VfL</h1></div><time dateTime={day}>{dateLabel}</time></div>
  <div className="today-slides" onTouchStart={e=>{touch.current={x:e.touches[0].clientX,y:e.touches[0].clientY};}} onTouchCancel={()=>{touch.current=null;}} onTouchEnd={e=>{const start=touch.current;touch.current=null;if(!start)return;const dx=e.changedTouches[0].clientX-start.x,dy=e.changedTouches[0].clientY-start.y;if(Math.abs(dx)>50&&Math.abs(dx)>Math.abs(dy)){select(active+(dx<0?1:-1));setPaused(true);}}}>
   {cards.map((card,i)=><article key={card.id} hidden={i!==active} className="today-slide" role="group" aria-roledescription="Folie" aria-label={`${i+1} von 3: ${card.label}`}>
    <img className="today-photo" src={images[card.id]} alt="" fetchPriority={i===0?'high':'auto'}/><div className="today-shade"/>
    <div className="today-content"><span className={'today-badge'+(card.warning?' warning':'')}>{card.label}</span><h2>{card.title}</h2>
     <div className="today-lines">{card.lines.map((line,n)=><p key={n}>{line}</p>)}</div>{card.detail&&<p className="today-detail">{card.detail}</p>}
     <a className="today-action" href={appPath(card.link)}>{card.action}<ArrowRight size={18}/></a>
    </div>
   </article>)}
  </div>
  <div className="today-controls"><button type="button" aria-label="Vorherige Information" onClick={()=>select(active-1)}><ChevronLeft size={19}/></button><div className="today-dots">{cards.map((c,i)=><button type="button" key={c.id} aria-label={c.label} aria-current={i===active?'true':undefined} onClick={()=>select(i)}><span/></button>)}<small>{active+1} / 3</small></div><button type="button" aria-label={paused?'Automatischen Wechsel starten':'Automatischen Wechsel pausieren'} aria-pressed={paused} onClick={()=>setPaused(v=>!v)}>{paused?<Play size={17}/>:<Pause size={17}/>}</button><button type="button" aria-label="Nächste Information" onClick={()=>select(active+1)}><ChevronRight size={19}/></button></div>
  {error&&<button className="today-retry" onClick={()=>setReload(v=>v+1)}><RefreshCw size={14}/> Erneut laden</button>}
 </section>;
}
