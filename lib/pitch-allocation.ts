import {isMatchDate,type Match,type MatchSchedule} from './matches';
import {BACK,FRONT,TURF,MAIN,trainingsFor,reservesFor,minutes,clock,fieldLabel,type Field,type Segment,type Training} from './training-plan';
export type Booking = {id:string;kind:'training'|'match'|'reserve';title:string;segments:Segment[];original?:Segment[];moved?:boolean;reason?:string;kickoff?:string;url?:string;teams?:{home:string;away:string;homeLogo?:string;awayLogo?:string}};
export type Allocation = {date:string;bookings:Booking[];changes:{team:string;time:string;from:string;to:string;reason:string}[];conflicts:string[];warnings:string[];automatic:boolean};
// Planning assumptions, NOT official match lengths. Includes warm-up, half-time and changeover.
export const MATCH_RULES={warmup:30,halfTime:15,changeover:15,playing:{G:60,F:40,E:50,D:60,C:70,B:80,A:90,senior:90}};
export const overlaps=(a:Segment,b:Segment)=>a.start<b.end&&b.start<a.end&&a.fields.some(f=>b.fields.includes(f));
const clashes=(segments:Segment[],bookings:Booking[])=>bookings.filter(b=>b.segments.some(s=>segments.some(t=>overlaps(s,t))));
const startOf=(t:Training)=>Math.min(...t.segments.map(s=>s.start));
const endOf=(t:Training)=>Math.max(...t.segments.map(s=>s.end));
function candidates(t:Training):Field[][]{
 const onGrass=t.segments[0].fields.some(f=>f.startsWith('r')||f==='corner');
 const grass:Field[][]=t.small?[['r1'],['r2'],['r3'],['corner']]:[['r1'],['r2','r3']];
 const regular=onGrass?[...grass,FRONT,BACK]:[FRONT,BACK,...grass];
 // Only the first men's team and youth may use the stadium, one half per session.
 const mainAllowed=t.team==='1. Herren'||/Junior(?:en|innen)/i.test(t.team);
 return mainAllowed?[...regular,['main1'],['main2']]:regular;
}
function seniorMain(m:Match){
 if(m.category!=='Herren')return false;
 const suffix=m.home.match(/Ramsdorf\s*(III|II|IV|I|\d+)?\s*$/i)?.[1]??'';
 return ['','I','II','III','1','2','3'].includes(suffix.toUpperCase());
}
export function allocate(date:string,schedule:MatchSchedule|null,sourceUsable:boolean,template:Training[]=trainingsFor(date)):Allocation{
 template=template.map(t=>t.team==='SF Nordvelen'?{...t,team:'Nordvelen'}:t);
 const result:Allocation={date,bookings:[],changes:[],conflicts:[],warnings:[],automatic:false};
 const originals:Booking[]=template.map(t=>({id:t.id,kind:'training',title:t.team,segments:t.segments}));
 const reserves:Booking[]=reservesFor(date).map((s,i)=>({id:'reserve-'+i,kind:'reserve',title:'Spielreserve',segments:[s]}));
 const inRange=schedule&&schedule.from<=date&&schedule.to>=date;
 let trusted=!!inRange&&sourceUsable&&!schedule.stale&&!schedule.omittedRows;
 if(!sourceUsable)result.warnings.push('PDF-Quelle nicht bestätigt: automatische Verschiebung pausiert.');
 if(!inRange)result.warnings.push('Spieldaten fehlen: Überschneidungen können nicht geprüft werden.');
 else if(schedule.stale||schedule.omittedRows)result.warnings.push('Spieldaten sind veraltet oder unvollständig: automatische Verschiebung pausiert.');
 if(inRange&&schedule.provisional)result.warnings.push('Die Quelle enthält vorläufige Ansetzungen. Sie werden vorsorglich eingeplant; Änderungen sind möglich.');
 const requests:{m:Match;segment:Segment;choices:Field[][]}[]=[];
 for(const original of inRange?schedule.matches:[]){
  let m=original;
  if(m.date!==date||m.location==='Auswärts'||/Ausfall|Absetzung|Nichtantritt|spielfrei/i.test(m.status))continue;
  if(/verlegt/i.test(m.status)){
   const moved=m.status.match(/(\d{2})\.(\d{2})\.(\d{4}) (\d{2}:\d{2})/);
   const target=moved?`${moved[3]}-${moved[2]}-${moved[1]}`:'';
   if(moved&&isMatchDate(target)){
    result.warnings.push(`${m.home}: verlegt auf ${moved[1]}.${moved[2]}.${moved[3]} um ${moved[4]}.`);
    if(target!==date)continue;
    m={...m,time:moved[4]};
   }else{result.warnings.push(`${m.home}: neuer Spieltermin ungeklärt. Automatische Verteilung pausiert.`);trusted=false;continue;}
  }
  if(!/^([01]\d|2[0-3]):[0-5]\d$/.test(m.time)){result.warnings.push(`${m.home}: Anstoßzeit fehlt. Keine sichere automatische Verteilung möglich.`);trusted=false;continue;}
  const youth=m.category.match(/^([A-G])-Junior(?:en|innen)$/)?.[1] as keyof typeof MATCH_RULES.playing|undefined;
  const known=youth||['Herren','Frauen','Altherren','Ü-Herren'].includes(m.category);
  if(!known){result.warnings.push(`${m.category}: Platzbedarf ungeklärt. Automatische Verteilung pausiert.`);trusted=false;continue;}
  // Festivals can run longer than a single match; never guess an unbounded reservation.
  if(m.url.includes('/spieltag/')){result.warnings.push(`${m.home}: Spielfest ohne bestätigte Endzeit. Automatische Verteilung pausiert.`);trusted=false;}
  const duration=MATCH_RULES.playing[youth??'senior'];
  const choices:Field[][]=seniorMain(m)?[MAIN]:youth&&['G','F','E'].includes(youth)?[FRONT,BACK]:[TURF];
  requests.push({m,segment:{start:Math.max(0,minutes(m.time)-MATCH_RULES.warmup),end:Math.min(1440,minutes(m.time)+duration+MATCH_RULES.halfTime+MATCH_RULES.changeover),fields:[]},choices});
 }
 // Whole-pitch matches first; half-pitch fixtures choose the side that displaces fewer sessions.
 requests.sort((a,b)=>b.choices[0].length-a.choices[0].length||a.segment.start-b.segment.start||a.m.id.localeCompare(b.m.id));
 const games:Booking[]=[];
 for(const {m,segment,choices} of requests){
  const free=choices.filter(fields=>!clashes([{...segment,fields}],games).length);
  free.sort((a,b)=>clashes([{...segment,fields:a}],originals).length-clashes([{...segment,fields:b}],originals).length);
  const fields=free[0]??choices[0];
  if(!free.length)result.conflicts.push(`${m.time} · ${m.home} – ${m.away}: gleichzeitige Spiele benötigen dieselbe Fläche. Vereinsabstimmung nötig.`);
  games.push({id:'match-'+m.id,kind:'match',title:`${m.home} – ${m.away}`,segments:[{...segment,fields}],kickoff:m.time,url:m.url,teams:{home:m.home,away:m.away,homeLogo:m.homeLogo,awayLogo:m.awayLogo}});
 }
 result.automatic=trusted;
 if(!trusted){result.bookings=[...originals,...games,...reserves];return result;}
 // Preserve unaffected sessions first. Only displaced teams use remaining capacity.
 const fixed:Booking[]=[...games];
 const displaced:{t:Training;cause:Booking[]}[]=[];
 for(const t of template){const cause=clashes(t.segments,fixed);if(cause.length)displaced.push({t,cause});else fixed.push({id:t.id,kind:'training',title:t.team,segments:t.segments});}
 // The fewest available alternatives first avoids taking the only field a large team can use.
 const options=displaced.map(d=>({...d,options:candidates(d.t).filter(fields=>!clashes([{start:startOf(d.t),end:endOf(d.t),fields}], [...fixed,...reserves]).length)}));
 options.sort((a,b)=>a.options.length-b.options.length||startOf(a.t)-startOf(b.t)||a.t.id.localeCompare(b.t.id));
 // Bounded exhaustive search: maximize assigned sessions, then retain deterministic preferred alternatives.
 let best:(Field[]|null)[]=[],bestCount=-1,bestMain=Infinity,nodes=0;
 const search=(i:number,placed:Booking[],picked:(Field[]|null)[],count:number,mainCount:number)=>{
  const maximum=count+options.length-i;
  if(++nodes>50000||maximum<bestCount||(maximum===bestCount&&mainCount>=bestMain))return;
  if(i===options.length){best=[...picked];bestCount=count;bestMain=mainCount;return;}
  const {t,options:available}=options[i];
  for(const fields of available){const segments=[{start:startOf(t),end:endOf(t),fields}];if(!clashes(segments,placed).length)search(i+1,[...placed,{id:t.id,kind:'training',title:t.team,segments}],[...picked,fields],count+1,mainCount+Number(fields.some(f=>MAIN.includes(f))));}
  search(i+1,placed,[...picked,null],count,mainCount);
 };
 search(0,[],[],0,0);
 for(const [i,{t,cause}] of options.entries()){
  const fields=best[i];const time=`${clock(startOf(t))}–${clock(endOf(t))}`;
  const from=[...new Set(t.segments.map(s=>fieldLabel(s.fields)))].join(' / ');
  const reason=cause.map(b=>b.kind==='match'?`Spiel: ${b.title}`:`Training: ${b.title}`).join('; ');
  if(fields){fixed.push({id:t.id,kind:'training',title:t.team,segments:[{start:startOf(t),end:endOf(t),fields}],original:t.segments,moved:true,reason});result.changes.push({team:t.team,time,from,to:fieldLabel(fields),reason});}
  else{result.conflicts.push(`${t.team} · ${time}: keine passende Ausweichfläche für die gesamte Einheit. ${reason}.`);fixed.push({id:t.id,kind:'training',title:t.team,segments:[],original:t.segments,reason});}
 }
 result.bookings=[...fixed,...reserves];return result;
}
