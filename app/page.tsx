import {appPath} from '@/frontend/api';
import {CalendarDays,DoorOpen,ExternalLink,HandHelping,MapPin,Trophy} from 'lucide-react';
import {TodayCarousel} from '@/components/today-carousel';
import clubLogo from '@/frontend/assets/vfl-logo.png';
import './home.css';
const links=[
 {href:'/platzbelegung',icon:CalendarDays,title:'Platzbelegung',text:'Trainingszeiten & Plätze'},
 {href:'/kabinenbelegung',icon:DoorOpen,title:'Kabinenbelegung',text:'Deine Kabine finden'},
 {href:'/ehrenamt',icon:HandHelping,title:'Helferbörse',text:'Gemeinsam anpacken'},
 {href:'/platzbelegung#upcoming-title',icon:Trophy,title:'Spiele & Ergebnisse',text:'Alle Mannschaften'}
];
export default function Home(){return <div className="home-shell">
 <header className="club-header"><div className="header-inner"><a className="club-brand" href={appPath('/')} aria-label="VfL Ramsdorf Startseite"><img className="club-logo" src={clubLogo} alt="VfL Ramsdorf e.V. 1936"/><span className="club-name">RAMSDORF<small>Vereins-App</small></span></a><span className="preview-badge">Version 0.10.7</span></div></header>
 <main className="home-main"><TodayCarousel/><h2 className="home-shortcuts-title">Schnellzugriff</h2><nav className="home-shortcuts" aria-label="Vereinsbereiche">{links.map(({href,icon:Icon,title,text})=><a key={href} href={appPath(href)}><Icon size={26}/><span><strong>{title}</strong><small>{text}</small></span></a>)}</nav>
 <a className="home-website" href="https://www.vfl-ramsdorf.de/" target="_blank" rel="noopener noreferrer">Zur VfL-Webseite<ExternalLink size={17}/></a><p className="home-location"><MapPin size={13}/> Ramsdorf · Seit 1936</p></main>
 </div>;}
