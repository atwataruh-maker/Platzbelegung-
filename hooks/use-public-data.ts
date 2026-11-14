import {useEffect,useState} from 'react';
import {apiFetch} from '@/frontend/api';
/** Independent source requests; a failed score source must not hide today's plans. */
export function usePublicData<T>(path:string){
 const [state,setState]=useState<{data:T|null;error:boolean;loading:boolean;path:string}>({data:null,error:false,loading:true,path});
 const [revision,setRevision]=useState(0);
 useEffect(()=>{
  const controller=new AbortController();let disposed=false;
  const timeout=setTimeout(()=>controller.abort(),45000);
  (async()=>{try{const r=await apiFetch(path,{cache:'no-store',signal:controller.signal});if(!r.ok)throw Error('Source unavailable');const data=await r.json() as T;if(!disposed)setState({data,error:false,loading:false,path});}
  catch{if(!disposed)setState(s=>({...s,error:true,loading:false,path}));}finally{clearTimeout(timeout);}})();
  const refresh=()=>{if(!document.hidden)setRevision(v=>v+1);};
  const timer=setInterval(refresh,60000);document.addEventListener('visibilitychange',refresh);
  return()=>{disposed=true;controller.abort();clearTimeout(timeout);clearInterval(timer);document.removeEventListener('visibilitychange',refresh);};
 },[path,revision]);
 return {...state,data:state.path===path?state.data:null,loading:state.path!==path||state.loading,retry:()=>setRevision(v=>v+1)};
}
