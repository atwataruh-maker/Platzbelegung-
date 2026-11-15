import {Home,MapPinned,DoorOpen,PersonStanding,HandHelping,Newspaper} from 'lucide-react';
import {appPath} from '@/frontend/api';
export function ClubNavigation({path}:{path:string}){
 const tabs=[{path:'/',label:'Heute',icon:Home},{path:'/platzbelegung',label:'Plätze',icon:MapPinned},{path:'/kabinenbelegung',label:'Kabinen',icon:DoorOpen},{path:'/breitensport',label:'Sport',icon:PersonStanding},{path:'/ehrenamt',label:'Helfen',icon:HandHelping},{path:'/bomber',label:'Bomber',icon:Newspaper}];
 return <nav className="club-tabs" aria-label="Schnellzugriff">{tabs.map(t=><a key={t.path} href={appPath(t.path)} aria-current={path===t.path||(t.path==='/'&&path==='/index.php')?'page':undefined}><t.icon size={23}/><span>{t.label}</span></a>)}</nav>;
}
