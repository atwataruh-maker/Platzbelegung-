import {SOURCE} from '@/lib/plan';
// Keep the existing PDF endpoint pointed at the active, supplied season plan.
export async function GET(){
 return new Response(null,{status:302,headers:{Location:SOURCE,'Cache-Control':'no-store'}});
}
