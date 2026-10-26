import {apiFetch as fetch,appPath} from '@/frontend/api';
import { ArrowUpRight, CalendarDays, DoorOpen, ExternalLink, HandHelping, MapPin, Users } from 'lucide-react';

const links = [
  { href: '/platzbelegung', icon: CalendarDays, eyebrow: 'Fußball', title: 'Platzbelegung', text: 'Trainingszeiten und Platzaufteilung schnell auf dem Handy ansehen.', tone: 'blue', action: 'Plan öffnen' },
  { href: '/kabinenbelegung', icon: DoorOpen, eyebrow: 'Fußball', title: 'Kabinenbelegung', text: 'Die vier Kabinen für Training, Heimteams und Gäste im Tagesüberblick.', tone: 'violet', action: 'Kabinen ansehen' },
  { href: '/ehrenamt', icon: HandHelping, eyebrow: 'Miteinander', title: 'Ehrenamt & Helfen', text: 'Gesuche entdecken und den Verein mit deiner Zeit oder deinem Können unterstützen.', tone: 'green', action: 'Helfer gesucht' },
];

export default function Home() {
  return <div className="hub-shell">
    <header className="club-header"><div className="header-inner">
      <a className="club-brand" href="https://www.vfl-ramsdorf.de/" target="_blank" rel="noopener noreferrer"><img className="club-logo" src="https://www.vfl-ramsdorf.de/img/VfLRamsdorf.png" alt="VfL Ramsdorf Logo"/><span className="club-name">RAMSDORF<small>Vereins-App</small></span></a>
      <span className="preview-badge">Version 0.10.4 · PHP</span>
    </div></header>
    <main className="hub-main">
      <div className="hub-kicker"><span className="hub-dot"/> Gemeinsam für Ramsdorf</div>
      <h1>Was möchtest du<br/><span>heute tun?</span></h1>
      <p className="hub-intro">Die wichtigsten Vereinsinformationen an einem Ort.</p>
      <section className="hub-grid" aria-label="Vereinsbereiche">
        {links.map(({ href, icon: Icon, eyebrow, title, text, tone, action }) => <a key={href} href={appPath(href)} className={"hub-card " + tone}>
          <div className="hub-card-top"><span className="hub-icon"><Icon size={25}/></span><ArrowUpRight size={20} className="hub-arrow"/></div>
          <div className="hub-card-copy"><span className="hub-eyebrow">{eyebrow}</span><h2>{title}</h2><p>{text}</p></div>
          <span className="hub-action">{action}<ArrowUpRight size={16}/></span>
        </a>)}
      </section>
      <section className="club-link-card"><div className="club-link-icon"><Users size={22}/></div><div><strong>Alles vom VfL</strong><p>Neuigkeiten, Mannschaften, Kurse und Kontakt auf der Vereinswebsite.</p></div><a href="https://www.vfl-ramsdorf.de/" target="_blank" rel="noopener noreferrer" aria-label="VfL Ramsdorf Website öffnen"><ExternalLink size={19}/></a></section>
      <p className="hub-location"><MapPin size={15}/> Ramsdorf · Seit 1936</p>
    </main>
  </div>;
}
