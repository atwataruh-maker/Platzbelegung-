import type {Match,MatchSchedule} from './matches';
export type RecentMatch=Match&{kickoffAt:string};
export type RecentSchedule=Omit<MatchSchedule,'matches'>&{matches:RecentMatch[];since:string;until:string};
export function recentMatches(data:RecentSchedule|null,now:Date):RecentMatch[]{
 return (data?.matches??[]).filter(m=>{const t=Date.parse(m.kickoffAt);return Number.isFinite(t)&&t<=now.getTime()&&t>=now.getTime()-48*3600000;}).sort((a,b)=>Date.parse(b.kickoffAt)-Date.parse(a.kickoffAt));
}
export function scoreLabel(m:Match){
 if(m.status)return m.status;
 if(m.result)return m.resultStale?'Letzter bekannter Stand':m.result.confirmed?'Bestätigtes Ergebnis':'Vorläufiger Spielstand';
 return 'Ergebnis noch nicht verfügbar';
}
export function incomplete(s:MatchSchedule|null|undefined){return !s||s.stale||s.provisional||!!s.omittedRows;}
export function teamKey(m:Match){return m.category+'|'+(/Ramsdorf/i.test(m.home)?m.home:m.away);}
export function safeMatchLink(url:string){return /^https:\/\/www\.fussball\.de\/(spiel|spieltag)\//.test(url)?url:'https://www.fussball.de/';}
