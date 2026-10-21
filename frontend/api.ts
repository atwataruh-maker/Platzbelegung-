declare global {interface Window {VFL?:{base:string;csrf:string;legalUrl?:string;privacyUrl?:string}}}
export function appPath(path:string){return (window.VFL?.base??'')+path;}
let token=window.VFL?.csrf??'';
export function csrf(value:string){token=value;}
export async function apiFetch(path:string,init:RequestInit={}):Promise<Response>{
 const headers=new Headers(init.headers);
 if(init.method&&!['GET','HEAD'].includes(init.method.toUpperCase())){
  // Refresh before a write: another tab or an expired session may have rotated the token.
  const response=await fetch(appPath('/api/auth'),{cache:'no-store',credentials:'same-origin',signal:init.signal});
  const data=await response.json();
  if(!response.ok||!data.csrf)throw new Error('Sitzung konnte nicht erneuert werden. Bitte neu anmelden.');
  token=data.csrf;
  headers.set('X-CSRF-Token',token);
 }
 return fetch(appPath(path),{...init,headers,credentials:'same-origin'});
}
