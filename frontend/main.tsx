import {createRoot} from 'react-dom/client';
import {Component,type ReactNode} from 'react';
import Home from '@/app/page';
import Pitch from '@/app/platzbelegung/page';
import Cabins from '@/app/kabinenbelegung/page';
import Helpers from '@/app/ehrenamt/page';
import Admin from './admin';
import {appPath} from './api';
import '@/app/globals.css';
import './strato.css';
class ErrorBoundary extends Component<{children:ReactNode},{failed:boolean}>{
 state={failed:false};static getDerivedStateFromError(){return {failed:true};}
 render(){return this.state.failed?<main><h1>Ansicht konnte nicht geladen werden</h1><p>Bitte lade die Seite neu.</p><a href={appPath('/')}>Zur Übersicht</a></main>:this.props.children;}
}
const path=location.pathname.slice((window.VFL?.base??'').length).replace(/\/$/,'')||'/';
const Page=({'/':Home,'/index.php':Home,'/platzbelegung':Pitch,'/kabinenbelegung':Cabins,'/ehrenamt':Helpers,'/admin':Admin} as Record<string,()=>ReactNode>)[path]??Home;
createRoot(document.getElementById('root')!).render(<ErrorBoundary><Page/><nav className="legal-links" aria-label="Verwaltung und Rechtliches"><a href={appPath('/admin')}>Admin-Anmeldung</a>{window.VFL?.legalUrl?.startsWith('https://')&&<a href={window.VFL.legalUrl}>Impressum</a>}{window.VFL?.privacyUrl?.startsWith('https://')&&<a href={window.VFL.privacyUrl}>Datenschutz</a>}</nav></ErrorBoundary>);
