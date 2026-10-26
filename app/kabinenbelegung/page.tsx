import {apiFetch as fetch,appPath} from '@/frontend/api';
'use client';
import {useEffect,useState} from 'react';
import {ArrowLeft,ArrowRight,CalendarDays,DoorOpen,ExternalLink,RefreshCw,AlertTriangle,LoaderCircle} from 'lucide-react';
import {Button} from '@/components/ui/button';
import {CabinPlan} from '@/components/cabin-plan';
import type {CabinPlan as CabinData} from '@/lib/cabin-allocation';
import {sevenDays,shiftMatchDate,isMatchDate} from '@/lib/matches';
import '../platzbelegung/pitch.css';

type DayPlan={date:string;cabins:CabinData;warnings:string[];matchesRetrievedAt:string|null};
const label=(date:string)=>new Intl.DateTimeFormat('de-DE',{weekday:'long',day:'2-digit',month:'long',timeZone:'Europe/Berlin'}).format(new Date(date+'T12:00:00Z'));

export default function CabinPage(){
 const [day,setDay]=useState(()=>sevenDays().from);
 const [reload,setReload]=useState(0),[data,setData]=useState<DayPlan|null>(null),[loading,setLoading]=useState(true),[error,setError]=useState('');
 const current=data?.date===day?data:null;
 useEffect(()=>{
  const controller=new AbortController();setLoading(true);setError('');setData(null);
  (async()=>{try{
   const response=await fetch('/api/belegung?date='+day,{cache:'no-store',signal:controller.signal});
   if(!response.ok)throw new Error('Cabin request failed');
   const result=await response.json() as DayPlan;
   if(!controller.signal.aborted)setData(result);
  }catch{if(!controller.signal.aborted)setError('Die Kabinenbelegung ist gerade nicht erreichbar. Bitte erneut laden.');}
  finally{if(!controller.signal.aborted)setLoading(false);}})();
  const interval=setInterval(()=>setReload(v=>v+1),300000);
  return()=>{controller.abort();clearInterval(interval);};
 },[day,reload]);
 const select=(value:string)=>{if(value!==day&&isMatchDate(value)){setDay(value);setData(null);setError('');}};
 const weekStart=shiftMatchDate(day,-((new Date(day+'T12:00:00Z').getUTCDay()+6)%7));
 return <div className="app-shell pitch-app cabin-page"><header className="club-header"><div className="header-inner"><a className="club-brand" href={appPath('/')} aria-label="Zur Startseite"><img className="club-logo" src="https://www.vfl-ramsdorf.de/img/VfLRamsdorf.png" alt="VfL Ramsdorf"/><span className="club-name">RAMSDORF<small>Vereins-App</small></span></a><div className="header-actions"><span className="preview-badge">Version 0.10.6</span><Button variant="ghost" size="icon" className="refresh" onClick={()=>setReload(v=>v+1)} disabled={loading} aria-label="Kabinenbelegung aktualisieren"><RefreshCw className={loading?'spin':''}/></Button></div></div></header>
 <main>
  <a href={appPath('/')} className="pitch-back"><ArrowLeft size={15}/> Übersicht</a>
  <div className="page-heading"><div><p className="eyebrow">Fußball / Umziehen & Ankommen</p><h1>Kabinenbelegung</h1><p className="intro">Kabinen für Training, Heimteams und Gäste.</p></div><span className="heading-symbol"><DoorOpen size={26}/></span></div>
  <div className="pitch-date-bar"><Button variant="outline" size="icon" aria-label="Kabinenbelegung vorheriger Tag" onClick={()=>select(shiftMatchDate(day,-1))}><ArrowLeft size={17}/></Button><label><CalendarDays size={17}/><input type="date" min="2000-01-01" max="2100-12-31" aria-label="Datum der Kabinenbelegung" value={day} onChange={e=>select(e.target.value)}/></label><Button variant="outline" size="icon" aria-label="Kabinenbelegung nächster Tag" onClick={()=>select(shiftMatchDate(day,1))}><ArrowRight size={17}/></Button><Button variant="outline" onClick={()=>select(sevenDays().from)}>Heute</Button></div>
  <div className="pitch-week" aria-label="Tag dieser Woche">{Array.from({length:7},(_,i)=>{const d=shiftMatchDate(weekStart,i);return <button key={d} onClick={()=>select(d)} aria-pressed={d===day} className={d===day?'active':''}><span>{['Mo','Di','Mi','Do','Fr','Sa','So'][i]}</span><strong>{Number(d.slice(-2))}</strong></button>;})}</div>
  <div className="allocation-banner" aria-live="polite" aria-atomic="true">
   {loading&&<div className="pitch-notice neutral"><LoaderCircle className="spin" size={20}/><div><strong>Kabinen werden abgeglichen</strong><p>{label(day)} · Training und Heimspiele</p></div></div>}
   {error&&<div className="pitch-notice danger" role="alert"><AlertTriangle size={20}/><div><strong>Abgleich nicht möglich</strong><p>{error}</p><Button variant="outline" onClick={()=>setReload(v=>v+1)}>Erneut laden</Button></div></div>}
   {current&&<>
    {!!current.cabins.notes?.length&&<div className="pitch-notice warning"><AlertTriangle size={20}/><div><strong>Geänderte Kabinenbelegung</strong>{current.cabins.notes.map(n=><p key={n}>{n}</p>)}</div></div>}
    {current.cabins.conflicts.length>0&&<div className="pitch-notice warning"><AlertTriangle size={20}/><div><strong>Abstimmung nötig · {current.cabins.conflicts.length} {current.cabins.conflicts.length===1?'Zuordnung offen':'Zuordnungen offen'}</strong><p>Die betroffenen Mannschaften stehen unter „Noch ohne Kabine“.</p></div></div>}
    {!current.cabins.confirmed&&<div className="pitch-notice warning"><AlertTriangle size={20}/><div><strong>Vorläufiger Kabinenplan</strong><p>Der Spielabgleich ist unvollständig. Weitere Heim- und Gastteams können hinzukommen.</p></div></div>}
   </>}
  </div>
  {current&&<><CabinPlan plan={current.cabins} title={label(day)}/><p className="pitch-updated">{current.matchesRetrievedAt?'Spiele abgeglichen: '+new Date(current.matchesRetrievedAt).toLocaleString('de-DE',{timeZone:'Europe/Berlin'})+' · ':''}Automatische Aktualisierung alle 5 Minuten bei geöffneter App.</p></>}
  <footer className="footer"><span>VfL Ramsdorf · Seit 1936</span><a href="https://www.vfl-ramsdorf.de/" target="_blank" rel="noopener noreferrer">Vereinswebsite <ExternalLink size={13}/></a></footer>
 </main></div>;
}
