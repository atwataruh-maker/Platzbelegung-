import type {Booking} from './pitch-allocation';
import {FRONT,BACK,type Field} from './training-plan';
export function laneEntries(bookings:Booking[],lane:Field[]){
 return bookings.flatMap(b=>{
  const segments=b.segments.filter(s=>s.fields.some(f=>lane.includes(f)));
  // Show only the remaining, actually unoccupied portions of a match reserve.
  if(b.kind==='reserve')return segments.flatMap(s=>{
   const busy=bookings.filter(x=>x.kind!=='reserve').flatMap(x=>x.segments).filter(x=>x.fields.some(f=>lane.includes(f))&&x.start<s.end&&s.start<x.end);
   const points=[...new Set([s.start,s.end,...busy.flatMap(x=>[Math.max(s.start,x.start),Math.min(s.end,x.end)])])].sort((a,b)=>a-b);
   return points.slice(0,-1).flatMap((start,i)=>busy.some(x=>x.start<points[i+1]&&start<x.end)?[]:[{...b,id:b.id+'-'+start,segments:[{...s,start,end:points[i+1]}]}]);
  });
  return segments.length?[{...b,segments}]:[];
 }).sort((a,b)=>a.segments[0].start-b.segments[0].start||a.title.localeCompare(b.title));
}

/** A single shared grid: full-pitch bookings span both halves, once. */
export function turfLayout(bookings:Booking[],halves:[Field[],Field[]]=[FRONT,BACK]){
 const entries:{key:string;booking:Booking;column:number;span:number;row:number}[]=[];
 for(const booking of bookings){
  if(booking.kind==='reserve')continue;
  // Split only when a booking's occupied half changes; preserve adjacent segments together.
  for(const segment of booking.segments){
   const front=segment.fields.some(f=>halves[0].includes(f)),back=segment.fields.some(f=>halves[1].includes(f));
   if(!front&&!back)continue;
   const column=front?1:2,span=front&&back?2:1;
   const previous=entries.at(-1);
   if(previous?.booking.id===booking.id&&previous.column===column&&previous.span===span&&previous.booking.segments.at(-1)!.end===segment.start){previous.booking.segments.push(segment);continue;}
   entries.push({key:booking.id+'-'+segment.start+'-'+column,booking:{...booking,segments:[segment]},column,span,row:0});
  }
 }
 for(const [i,lane] of halves.entries())for(const booking of laneEntries(bookings,lane).filter(b=>b.kind==='reserve'))entries.push({key:booking.id+'-'+i,booking,column:i+1,span:1,row:0});
 entries.sort((a,b)=>a.booking.segments[0].start-b.booking.segments[0].start||b.span-a.span||a.column-b.column||a.key.localeCompare(b.key));
 // Equal start times share a row only when they occupy separate columns.
 let row=0,start=-1,occupied:number[]=[];
 for(const entry of entries){
  const time=entry.booking.segments[0].start;
  const columns=Array.from({length:entry.span},(_,i)=>entry.column+i);
  if(time!==start||columns.some(c=>occupied.includes(c))){row++;start=time;occupied=[];}
  entry.row=row;occupied.push(...columns);
 }
 return entries;
}
