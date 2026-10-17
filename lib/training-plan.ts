// Transcribed from the user-provided Belegungsplan.pdf (Sommersaison), verified 2026-10-16.
// Keep combined training groups and their complete source footprint together.
export type Field = 'r1'|'r2'|'r3'|'corner'|'k1a'|'k1b'|'k2a'|'k2b'|'main1'|'main2';
export type Segment = { start:number; end:number; fields:Field[] };
export type Training = { id:string; day:number; team:string; small:boolean; season:'all'|'summer'|'winter'; activeFrom?:string; segments:Segment[] };
export const FRONT:Field[]=['k1a','k1b'];
export const BACK:Field[]=['k2a','k2b'];
export const TURF:Field[]=[...FRONT,...BACK];
export const GRASS:Field[]=['r1','r2','r3'];
export const MAIN:Field[]=['main1','main2'];
export const minutes=(time:string)=>{const [h,m]=time.split(':').map(Number);return h*60+m;};
export const clock=(value:number)=>`${String(Math.floor(value/60)).padStart(2,'0')}:${String(value%60).padStart(2,'0')}`;
const s=(start:string,end:string,fields:Field[]):Segment=>({start:minutes(start),end:minutes(end),fields});
const t=(day:number,team:string,start:string,end:string,fields:Field[],season:Training['season']='all',segments?:Segment[]):Training=>({id:`${day}-${team}`,day,team,small:/^[EFG](?:\d|[ /-])/.test(team),season,segments:segments??[s(start,end,fields)]});
export const TRAINING:Training[]=[
 t(1,'D1-Junioren','16:30','18:00',['r2']),
 t(1,'E1-Junioren','17:00','18:30',['r3']),
 t(1,'D2-Junioren','17:30','19:00',FRONT),
 t(1,'D1-Juniorinnen','17:30','19:00',BACK),
 t(1,'D2-Juniorinnen','18:00','19:30',['r1']),
 t(1,'A-Junioren','19:00','20:30',FRONT),
 t(1,'B-Junioren','19:00','20:30',BACK),
 t(2,'G/F-Juniorinnen','16:30','17:30',FRONT),
 t(2,'E1/E2-Juniorinnen','17:30','19:00',BACK),
 t(2,'E3-Junioren','17:30','19:00',['r1']),
 t(2,'C-Junioren','18:00','19:30',FRONT),
 t(2,'E2-Junioren','18:00','19:30',['r2']),
 t(2,'3. Herren','19:00','20:30',BACK),
 t(2,'VfL Ramsdorf Damen','19:00','20:30',['r1']),
 t(2,'1. Herren','19:30','21:00',FRONT),
 t(3,'D1-Junioren','16:30','18:00',BACK),
 t(3,'F1/F2-Junioren','17:00','18:00',['r2','r3']),
 t(3,'C-Juniorinnen','17:30','19:00',FRONT),
 t(3,'D1-Juniorinnen','17:30','19:00',['r1']),
 t(3,'D2-Juniorinnen','18:00','19:30',BACK),
 t(3,'E2-Junioren','18:00','19:30',['r2']),
 t(3,'2. Herren','19:00','20:30',FRONT),
 t(3,'Alte Herren','19:30','21:00',BACK),
 t(4,'D2-Junioren','17:00','18:30',['r2']),
 t(4,'E1-Junioren','17:30','19:00',FRONT),
 t(4,'E3-Junioren','17:30','19:00',BACK),
 t(4,'C-Junioren','17:30','19:00',['r1']),
 t(4,'1. Herren','19:00','20:30',FRONT),
 {...t(4,'SF Nordvelen','19:00','20:30',BACK),activeFrom:'2026-10-01'},
 t(4,'A-Junioren','19:00','20:30',['r1']),
 t(4,'B-Junioren','19:00','20:30',['r2']),
 t(5,'F1/F2-Junioren','16:30','17:30',BACK),
 t(5,'G-Junioren / G-F-Juniorinnen','17:00','18:00',['r3','corner']),
 t(5,'E1/E2-Juniorinnen','17:30','19:00',['r1']),
 t(5,'C-Juniorinnen','17:30','19:00',['r2']),
 t(5,'VfL Ramsdorf Damen','18:00','19:30',BACK),
 t(5,'2. Herren','19:00','20:30',['r1']),
 t(5,'3. Herren','19:00','20:30',['r2']),
];
export function trainingsFor(date:string):Training[]{
 const day=new Date(date+'T12:00:00Z').getUTCDay();
 // The supplied summer plan has no end date. It remains the selected plan until replaced.
 return TRAINING.filter(t=>t.day===day&&(!t.activeFrom||date>=t.activeFrom));
}
export function reservesFor(date:string):Segment[]{
 return new Date(date+'T12:00:00Z').getUTCDay()===5 ? [s('16:30','20:30',FRONT),s('17:30','18:00',BACK),s('19:30','20:30',BACK)] : [];
}
export function fieldLabel(fields:Field[]):string {
 if(!fields.length)return 'Keine freie Fläche';
 const parts:string[]=[];
 if(MAIN.every(f=>fields.includes(f)))parts.push('Hauptplatz komplett');
 else for(const [id,name] of [['main1','Hauptplatz · Hälfte 1'],['main2','Hauptplatz · Hälfte 2']] as const)if(fields.includes(id))parts.push(name);
 if(GRASS.every(f=>fields.includes(f)))parts.push('Trainingsrasen komplett');
 else for(const [id,name] of [['r1','Rasen 1/3'],['r2','Rasen 2/3'],['r3','Rasen 3/3'],['corner','Rasen · Reserve-Ecke']] as const)if(fields.includes(id))parts.push(name);
 if(TURF.every(f=>fields.includes(f)))parts.push('Kunstrasen komplett');
 else for(const [group,label] of [[FRONT,'Kunstrasen vorne'],[BACK,'Kunstrasen hinten']] as const){const n=group.filter(f=>fields.includes(f)).length;if(n)parts.push(label+(n===1?' · Viertelfeld':''));}
 return parts.join(' + ');
}
