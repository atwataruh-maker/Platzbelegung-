import type {MatchSchedule} from './matches';
import type {Allocation} from './pitch-allocation';
import type {CabinPlan} from './cabin-allocation';
export type TodayData={date:string;schedule:MatchSchedule|null;allocation:Allocation&{cabins:CabinPlan}};
export type TodayCard={id:'games'|'pitch'|'cabins';label:string;title:string;lines:string[];detail?:string;warning:boolean;link:string;action:string};
export function todayCards(date:string,data:TodayData|null,loading:boolean,error:boolean):TodayCard[]{
 const base:TodayCard[]=[
  {id:'games',label:'Heutige Spiele',title:'Spiele werden geladen',lines:[],warning:false,link:'/platzbelegung?date='+date+'#upcoming-title',action:'Heutige Spiele ansehen'},
  {id:'pitch',label:'Platzbelegung',title:'Plätze werden abgeglichen',lines:[],warning:false,link:'/platzbelegung?date='+date+'#pitch-title',action:'Platzbelegung ansehen'},
  {id:'cabins',label:'Kabinenbelegung',title:'Kabinen werden abgeglichen',lines:[],warning:false,link:'/kabinenbelegung?date='+date,action:'Kabinenbelegung ansehen'}
 ];
 if(loading&&!data)return base;
 if(error||!data||data.date!==date||data.allocation.date!==date)return base.map(c=>({...c,title:'Abgleich derzeit nicht möglich',lines:['Aktuelle Informationen konnten nicht geladen werden.'],warning:true}));
 const {schedule:s,allocation:a}=data;
 const complete=!!s&&s.from<=date&&s.to>=date&&!s.stale&&!s.provisional&&!s.omittedRows;
 const matches=s?.matches.filter(m=>m.date===date).sort((a,b)=>a.time.localeCompare(b.time))??[];
 const games=base[0];
 games.title=matches.length?`${matches.length} ${matches.length===1?'Spiel':'Spiele'} heute`:complete?'Heute keine Spiele':'Spielplan noch unvollständig';
 games.lines=matches.slice(0,2).map(m=>`${m.category} · ${m.time}${/^\d{2}:\d{2}$/.test(m.time)?' Uhr':''} · ${m.location}\n${m.home} – ${m.away}${m.status?' · '+m.status:''}${m.result?' · '+m.result.home+':'+m.result.away+(m.resultStale?' (letzter Stand)':m.result.confirmed?'':' (vorläufig)'):m.resultUnavailable?' · Ergebnis noch nicht verfügbar':''}`);
 if(!matches.length)games.lines=[complete?'Für heute sind keine Spiele angesetzt.':'Der Spielabgleich ist derzeit nicht vollständig möglich.'];
 games.warning=!complete;games.detail=!complete?'Vorläufiger Stand – weitere Spiele oder Änderungen sind möglich.':matches.length>2?`Alle ${matches.length} Spiele über den Direktlink.`:undefined;
 const pitch=base[1],cabins=base[2];
 for(const [card,confirmed,changes,conflicts,regular] of [
  [pitch,complete&&a.automatic,a.changes.map(c=>`${c.team} · ${c.time}\n${c.from} → ${c.to}\nGrund: ${c.reason}`),a.conflicts,'Platzbelegung'],
  [cabins,complete&&a.cabins.confirmed,a.cabins.notes??[],a.cabins.conflicts,'Kabinenbelegung']
 ] as const){
  card.warning=!!changes.length||!!conflicts.length||!confirmed;
  card.title=conflicts.length?'Heute ist Abstimmung nötig':changes.length?`${changes.length} ${changes.length===1?'Änderung':'Änderungen'} heute`:confirmed?`Reguläre ${regular}`:'Belegung noch vorläufig';
  card.lines=conflicts.length?[conflicts[0]]:changes.length?[changes[0]]:[confirmed?`Heute gilt die reguläre ${regular}. Keine Verschiebungen.`:'Der Spielabgleich ist unvollständig. Änderungen können noch hinzukommen.'];
  card.detail=!confirmed?'Vorläufig – aktuelle Spiele konnten nicht vollständig abgeglichen werden.':conflicts.length?`${conflicts.length} offene ${conflicts.length===1?'Zuordnung':'Zuordnungen'}${changes.length?` und ${changes.length} Änderungen`:''} im Tagesplan.`:changes.length>1?`Alle ${changes.length} Änderungen im Tagesplan.`:undefined;
 }
 return base;
}
