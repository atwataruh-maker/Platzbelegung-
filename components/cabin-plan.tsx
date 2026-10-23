import {DoorOpen,LockKeyhole} from 'lucide-react';
import {CABINS,GENDER_LABEL,isHomeWomensTeam,type CabinPlan as Plan} from '@/lib/cabin-allocation';
import {clock} from '@/lib/training-plan';

export function CabinPlan({plan,title='Kabinenbelegung'}:{plan:Plan;title?:string}){
 const unassigned=plan.bookings.filter(b=>!b.room);
 return <section className="pitch-section cabin-section" aria-labelledby="cabin-title">
  <div className="pitch-section-heading"><div><p className="eyebrow">Dein Tagesplan</p><h2 id="cabin-title">{title}</h2></div><span>4 Kabinen</span></div>
  <p className="cabin-intro">Alle Jugendteams gleichen Geschlechts dürfen sich eine Kabine teilen. Die 1. und 2. Herren sowie die Damen nutzen ihre Kabine allein.</p>
  {!plan.confirmed&&<p className="pitch-paused">Vorläufiger Kabinenplan: Die Spieldaten sind nicht vollständig bestätigt. Weitere Heim- und Gastteams können hinzukommen.</p>}
  <div className="cabin-grid">{CABINS.map(cabin=>{
   const entries=plan.bookings.filter(b=>b.room===cabin.id);
   return <section key={cabin.id} className="cabin-room" aria-label={cabin.name}>
    <header><DoorOpen size={21}/><div><h3>{cabin.name}</h3><p>{cabin.note}</p></div></header>
    {entries.length?<ul>{entries.map(b=><li key={b.id} className={'cabin-booking '+b.gender}>
     <div className="cabin-time"><time>{clock(b.start)}–{clock(b.end)}</time>{(b.fixed||isHomeWomensTeam(b))&&<span><LockKeyhole size={11}/> Alleinige Nutzung</span>}</div>
     <strong>{b.team}</strong><div className="cabin-meta"><span>{GENDER_LABEL[b.gender]}</span>{b.category&&<span>{b.category}</span>}<span>{b.activities.join(' + ')}</span></div>
     {!!b.sharingWith?.length&&<p className="cabin-sharing">Gemeinsam genutzt mit: {b.sharingWith.join(', ')}</p>}
    </li>)}</ul>:<p className="pitch-empty">{plan.confirmed?'Keine Belegung im Tagesplan':'Keine bekannte Belegung'}</p>}
   </section>;
  })}</div>
  {unassigned.length>0&&<div className="pitch-unassigned cabin-unassigned"><h3>Noch ohne Kabine · {unassigned.length}</h3><ul>{unassigned.map(b=><li key={b.id}><strong>{b.team}{b.category?' · '+b.category:''}</strong><span>{clock(b.start)}–{clock(b.end)} · {GENDER_LABEL[b.gender]}</span><p>{b.reason}</p></li>)}</ul></div>}
  <details className="pitch-rules cabin-rules"><summary>Zuordnung & Umkleidezeiten</summary><div>
   <p><strong>Feste Heimkabinen:</strong> Die 1. Herren nutzen immer die 1. Heimkabine, die 2. Herren immer die 2. Heimkabine – bei Heimspielen und Training. Außerhalb ihrer Belegungszeiten können andere Heimteams diese Kabinen nutzen.</p>
   <p><strong>Damen:</strong> Die Damen bekommen bei Training und Heimspielen ausschließlich die 1. oder 2. Heimkabine zur alleinigen Nutzung. Ist keine davon für den gesamten Zeitraum frei, wird ein Konflikt angezeigt.</p>
   <p><strong>Gastteams:</strong> Zuerst die 1. Auswärtskabine, bei Bedarf die 3. Heim- / 2. Auswärtskabine. Beide dürfen von Heimteams genutzt werden, wenn dort während ihres gesamten Zeitraums keine Gäste eingeplant sind. Die festen Heimkabinen werden zuerst berücksichtigt, danach Gäste, weitere Spielteams und Trainings. Auswärtsspiele außerhalb Ramsdorfs belegen hier keine Kabine.</p>
   <p><strong>Mittwochs ab 28.10.2026:</strong> C- und D1-Juniorinnen bekommen bevorzugt die große 1. Heimkabine, D1- und E2-Junioren die 3. Heimkabine. Feste Herrenbelegungen, Spiele und Geschlechtertrennung werden weiterhin berücksichtigt. Abweichungen erscheinen im Hinweisbanner.</p>
   <p><strong>Gemeinsame Nutzung:</strong> Alle Jugendteams von G bis A gleichen Geschlechts dürfen bei Bedarf eine Kabine teilen, auch über Altersklassen hinweg. Dies gilt sowohl für Heimteams untereinander als auch für Gastteams untereinander. Erwachsene nutzen eine Kabine allein. Heim- und Gastteams werden nicht gleichzeitig in dieselbe Kabine gelegt.</p>
   <p><strong>Geschlechtertrennung:</strong> Die Zuordnung folgt der Mannschaftsbezeichnung bzw. Spielklasse. Gemeinsam aufgeführte Jungen- und Mädchengruppen erhalten getrennte Kabinen. Unklare Angaben werden zur Abstimmung angezeigt.</p>
   <p><strong>Planungsannahmen:</strong> Training: 15 Minuten vorher bis 15 Minuten nachher. Spiele: 30 Minuten vor Anstoß bis 30 Minuten nach dem angenommenen Spielende, einschließlich Halbzeit. Die Kabine bleibt während der gesamten Einheit reserviert. Diese Umkleidezeiten sind Vorgaben der App und stehen nicht in der PDF.</p>
  </div></details>
 </section>;
}
