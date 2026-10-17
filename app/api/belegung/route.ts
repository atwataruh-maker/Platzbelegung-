import {GET as getMatches} from '../spiele/route';
import {PLAN_IMPORTED_AT,PLAN_LABEL} from '@/lib/plan';
import {isMatchDate,sevenDays,type MatchSchedule} from '@/lib/matches';
import {allocate} from '@/lib/pitch-allocation';
import {allocateCabins} from '@/lib/cabin-allocation';
export type SourceState={state:'verified';origin:'upload';checkedAt:string};
export async function GET(request:Request){
 const date=new URL(request.url).searchParams.get('date')??sevenDays().from;
 if(!isMatchDate(date))return Response.json({error:'Bitte ein gültiges Datum auswählen.'},{status:400});
 // The owner supplied and confirmed this current plan; the older website PDF is no longer a source.
 const source:SourceState={state:'verified',origin:'upload',checkedAt:PLAN_IMPORTED_AT};
 const matchResponse=await getMatches(new Request(new URL('/api/spiele?date='+date,request.url)));
 const schedule:MatchSchedule|null=matchResponse.ok?await matchResponse.json():null;
 const allocation=allocate(date,schedule,true);
 return Response.json({...allocation,cabins:allocateCabins(allocation,schedule),source,season:PLAN_LABEL,matchesRetrievedAt:schedule?.retrievedAt??null},{headers:{'Cache-Control':'no-store'}});
}
