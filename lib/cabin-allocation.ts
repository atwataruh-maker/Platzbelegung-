import type {Allocation} from './pitch-allocation';
import type {MatchSchedule} from './matches';
import {clock} from './training-plan';

export const CABINS = [
 {id:'home1',name:'1. Heimkabine',note:'Feste Kabine der 1. Herren'},
 {id:'home2',name:'2. Heimkabine',note:'Feste Kabine der 2. Herren'},
 {id:'shared',name:'3. Heim- / 2. Auswärtskabine',note:'Heimteams oder zusätzliche Gäste'},
 {id:'away1',name:'1. Auswärtskabine',note:'Bevorzugte Kabine für Gastteams'},
] as const;
export type CabinId = typeof CABINS[number]['id'];
export type Gender = 'male'|'female'|'unknown';
export const GENDER_LABEL:Record<Gender,string>={male:'Jungen / Männer',female:'Mädchen / Frauen',unknown:'Zuordnung offen'};
// Planning defaults, shown in the UI; these are not timings from the PDF.
export const CABIN_TIMES={trainingBefore:15,trainingAfter:15,matchAfterPitch:15};
export type CabinBooking={
 id:string;team:string;clubTeam?:string;category?:string;gender:Gender;start:number;end:number;room:CabinId|null;
 fixed:CabinId|null;guest:boolean;shareEligible:boolean;sharingWith?:string[];activities:string[];sourceIds:string[];reason?:string;
};
export type CabinPlan={bookings:CabinBooking[];confirmed:boolean;conflicts:string[];notes?:string[]};
const concurrent=(a:CabinBooking,b:CabinBooking)=>a.start<b.end&&b.start<a.end;

function fixedTraining(team:string):CabinId|null{
 return team==='1. Herren'?'home1':team==='2. Herren'?'home2':null;
}
function fixedMatch(team:string,category:string,ours:boolean):CabinId|null{
 if(!ours||category!=='Herren')return null;
 const match=team.match(/^VfL\s+Ramsdorf(?:\s+(I|II|1|2))?$/i);
 if(!match)return null;
 return ['II','2'].includes((match[1]??'I').toUpperCase())?'home2':'home1';
}
function genderOf(team:string):Gender{
 const female=/Juniorinnen|Damen|Frauen/i.test(team),male=/Junioren|Herren|Männer/i.test(team);
 return female===male?'unknown':female?'female':'male';
}
function youthSharing(label:string):boolean{
 const group=label.match(/^(.+)-Junior(?:en|innen)$/i)?.[1];
 // All youth age groups may share, including combined teams.
 return !!group&&group.split(/\s*[/-]\s*/).every(part=>/^[A-G]\d*$/i.test(part));
}
export function isHomeWomensTeam(b:CabinBooking):boolean{
 return !b.guest&&b.gender==='female'&&!b.shareEligible&&(b.category==='Frauen'||/\b(Damen|Frauen)\b/i.test(b.team));
}
function canShare(a:CabinBooking,b:CabinBooking):boolean{
 return a.shareEligible&&b.shareEligible&&a.gender!=='unknown'&&a.gender===b.gender&&a.guest===b.guest&&!a.fixed&&!b.fixed;
}
function ownTrainingTeam(team:string,category:string,trainingNames:string[]):string|undefined{
 const name=team.match(/^VfL\s+Ramsdorf(?:\s+(I|II|III|IV|[1-9]\d*))?$/i);
 if(!name)return undefined;
 const suffix=(name[1]??'1').toUpperCase();
 const number=({I:1,II:2,III:3,IV:4} as Record<string,number>)[suffix]??Number(suffix);
 const youth=category.match(/^([A-G])-(Junioren|Juniorinnen)$/);
 const names=youth?[`${youth[1]}${number}-${youth[2]}`,...(number===1?[`${youth[1]}-${youth[2]}`]:[])]:category==='Herren'?[`${number}. Herren`]:category==='Frauen'&&number===1?['VfL Ramsdorf Damen']:category==='Altherren'&&number===1?['Alte Herren']:[];
 return names.find(n=>trainingNames.includes(n));
}
// Recurring trainer feedback: simultaneous girls' teams need the larger room.
function wednesdayRoom(b:CabinBooking,date:string):CabinId|null{
 if(date<'2026-10-28'||new Date(date+'T12:00:00Z').getUTCDay()!==3||b.guest||b.fixed||b.activities.length!==1||b.activities[0]!=='Training')return null;
 if(['C-Juniorinnen','D1-Juniorinnen'].includes(b.team))return 'home1';
 if(['D1-Junioren','E2-Junioren'].includes(b.team))return 'shared';
 return null;
}
function candidates(b:CabinBooking,date:string):CabinId[]{
 if(b.fixed)return [b.fixed];
 if(isHomeWomensTeam(b))return ['home1','home2'];
 const regular:CabinId[]=b.guest?['away1','shared']:['shared','home1','home2','away1'];
 const preferred=wednesdayRoom(b,date);return preferred?[preferred,...regular.filter(r=>r!==preferred)]:regular;
}

export function allocateCabins(allocation:Allocation,schedule:MatchSchedule|null):CabinPlan{
 const requests:CabinBooking[]=[];
 const trainingNames=allocation.bookings.filter(b=>b.kind==='training').map(b=>b.title);
 for(const b of allocation.bookings){
  if(b.kind==='reserve')continue;
  const segments=b.segments.length?b.segments:b.original??[];
  if(!segments.length)continue;
  const start=Math.min(...segments.map(s=>s.start)),end=Math.max(...segments.map(s=>s.end));
  if(b.kind==='training'){
   // A combined boys/girls session still requires separate changing rooms.
   const groups=/Junioren/i.test(b.title)&&/Juniorinnen/i.test(b.title)?b.title.split(/\s+\/\s+/):[b.title];
   groups.forEach((team,i)=>requests.push({id:b.id+'-cabin-'+i,team,clubTeam:team,gender:genderOf(team),start:Math.max(0,start-CABIN_TIMES.trainingBefore),end:Math.min(1440,end+CABIN_TIMES.trainingAfter),room:null,fixed:fixedTraining(team),guest:false,shareEligible:youthSharing(team),activities:['Training'],sourceIds:[b.id]}));
  }else{
   // Reuse validated, non-cancelled home fixtures and their effective (possibly moved) times.
   const match=schedule?.matches.find(m=>'match-'+m.id===b.id);
   if(!match||!b.teams)continue;
   for(const side of ['home','away'] as const){
    const ours=side==='home'||match.location==='Vereinsduell';
    const team=b.teams[side],fixed=fixedMatch(team,match.category,ours);
    const clubTeam=fixed==='home1'?'1. Herren':fixed==='home2'?'2. Herren':ours?ownTrainingTeam(team,match.category,trainingNames):undefined;
    requests.push({id:b.id+'-'+side,team:clubTeam??team,clubTeam,category:match.category,gender:genderOf(match.category),start,end:Math.min(1440,end+CABIN_TIMES.matchAfterPitch),room:null,fixed,guest:!ours,shareEligible:youthSharing(match.category),activities:[side==='home'?'Heimspiel':ours?'Vereinsduell':'Gastspiel'],sourceIds:[b.id]});
   }
  }
 }
 // A recognisable club team playing during its regular training needs one cabin, not two.
 requests.sort((a,b)=>a.start-b.start||a.id.localeCompare(b.id));
 const merged:CabinBooking[]=[];
 for(const request of requests){
  const same=request.clubTeam?merged.find(b=>b.clubTeam===request.clubTeam&&b.gender===request.gender&&concurrent(b,request)):undefined;
  if(same){same.start=Math.min(same.start,request.start);same.end=Math.max(same.end,request.end);same.category??=request.category;same.shareEligible&&=request.shareEligible;same.activities=[...new Set([...same.activities,...request.activities])];same.sourceIds.push(...request.sourceIds);}
  else merged.push({...request});
 }
 const fixed=merged.filter(b=>b.fixed&&b.gender!=='unknown').map(b=>({...b,room:b.fixed}));
 const pending=merged.filter(b=>!b.fixed&&b.gender!=='unknown').map(b=>({b,rooms:candidates(b,allocation.date).filter(room=>!fixed.some(f=>f.room===room&&concurrent(b,f)))}));
 // Fixed cabins first; protect guests, then fixtures, then maximize assigned training.
 pending.sort((a,b)=>Number(b.b.guest)-Number(a.b.guest)||Number(b.b.activities.some(x=>x!=='Training'))-Number(a.b.activities.some(x=>x!=='Training'))||a.rooms.length-b.rooms.length||a.b.start-b.b.start||a.b.id.localeCompare(b.b.id));
 const preferencePenalty=4*(pending.length+1)**2;
 const weight=pending.length+1;
 const scores=pending.map(({b})=>Number(b.guest)*weight*weight+Number(b.activities.some(x=>x!=='Training'))*weight+1);
 const remaining=Array(pending.length+1).fill(0);
 for(let i=pending.length-1;i>=0;i--)remaining[i]=remaining[i+1]+scores[i];
 let best:(CabinId|null)[]=[],bestScore=-1,bestCost=Infinity,nodes=0;
 function search(i:number,placed:CabinBooking[],picked:(CabinId|null)[],score:number,cost:number){
  if(++nodes>50000)return;
  const maximum=score+remaining[i];
  if(maximum<bestScore||(maximum===bestScore&&cost>=bestCost))return;
  if(i===pending.length){best=[...picked];bestScore=score;bestCost=cost;return;}
  const {b,rooms}=pending[i];
  for(const room of rooms){
   const neighbours=placed.filter(p=>p.room===room&&concurrent(b,p));
   if(neighbours.every(p=>canShare(b,p)))search(i+1,[...placed,{...b,room}],[...picked,room],score+scores[i],cost+candidates(b,allocation.date).indexOf(room)+neighbours.length*4+(wednesdayRoom(b,allocation.date)&&room!==wednesdayRoom(b,allocation.date)?preferencePenalty:0));
  }
  search(i+1,placed,[...picked,null],score,cost);
 }
 search(0,[],[],0,0);
 const assigned:CabinBooking[]=[...fixed,...pending.map(({b},i)=>({...b,room:best[i]??null})),...merged.filter(b=>b.gender==='unknown')];
 const conflicts:string[]=[];
 for(const b of assigned){
  if(b.room){b.sharingWith=assigned.filter(other=>other.id!==b.id&&other.room===b.room&&concurrent(b,other)).map(other=>other.team);continue;}
  b.reason=b.gender==='unknown'?'Geschlechtszuordnung fehlt in der Quelle. Bitte mit der Kabinenkoordination klären.':isHomeWomensTeam(b)?'Für die Damen ist weder die 1. noch die 2. Heimkabine im gesamten Zeitraum allein verfügbar. Bitte mit der Kabinenkoordination abstimmen.':'Keine konfliktfreie Kabinenzuordnung für den gesamten Zeitraum ermittelt. Bitte mit der Kabinenkoordination abstimmen.';
  conflicts.push(`${b.team}${b.category?' · '+b.category:''} · ${clock(b.start)}–${clock(b.end)}: ${b.reason}`);
 }
 assigned.sort((a,b)=>a.start-b.start||a.team.localeCompare(b.team)||a.id.localeCompare(b.id));
 const regular=assigned.filter(b=>wednesdayRoom(b,allocation.date));
 const notes=regular.length?['Mittwochsregel: C- und D1-Juniorinnen nutzen bevorzugt die 1. Heimkabine, D1- und E2-Junioren die 3. Heimkabine.']:[];
 for(const b of regular)if(b.room!==wednesdayRoom(b,allocation.date))notes.push(`${b.team}: Die Mittwochszuordnung ist wegen anderer Belegungen nicht möglich. ${b.room?'Bitte die zugeteilte Kabine im Tagesplan beachten.':'Bitte mit der Kabinenkoordination abstimmen.'}`);
 return {bookings:assigned,confirmed:allocation.automatic,conflicts,...(notes.length?{notes}:{})};
}
