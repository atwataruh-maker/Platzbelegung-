// Hallenplan (winter): additional indoor times, independent of the pitch allocation.
export type HallSlot={day:number;hall:string;start:string;end:string;team?:string};
export type Hall={id:string;name:string;venue?:string};
export type HallPlan={title:string;validFrom:string;validTo:string;note?:string;halls:Hall[];slots:HallSlot[]};
export type HallDay={active:boolean;halls:(Hall&{slots:HallSlot[]})[];teams:number;free:number};

const weekday=(date:string)=>new Date(date+'T12:00:00Z').getUTCDay();
export const inSeason=(plan:HallPlan,date:string)=>date>=plan.validFrom&&date<=plan.validTo;
/** Slots of one date per hall, earliest first; empty outside the plan's validity. */
export function hallDay(plan:HallPlan|null|undefined,date:string):HallDay{
 if(!plan)return {active:false,halls:[],teams:0,free:0};
 const active=inSeason(plan,date),day=weekday(date);
 const halls=plan.halls.map(h=>({...h,slots:active?plan.slots.filter(s=>s.hall===h.id&&s.day===day).sort((a,b)=>a.start.localeCompare(b.start)):[]}));
 const all=halls.flatMap(h=>h.slots);
 return {active,halls,teams:all.filter(s=>s.team).length,free:all.filter(s=>!s.team).length};
}
export const germanDate=(date:string)=>`${date.slice(8,10)}.${date.slice(5,7)}.${date.slice(0,4)}`;
