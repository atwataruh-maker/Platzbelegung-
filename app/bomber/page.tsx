import {Newspaper,ExternalLink,RefreshCw} from 'lucide-react';
import {ClubHeader} from '@/components/club-header';
import {usePublicData} from '@/hooks/use-public-data';
type Issue={url:string;label:string;source:string;retrievedAt:string;stale:boolean};
export default function Bomber(){
 const {data,error,loading,retry}=usePublicData<Issue>('/api/bomber');
 const safe=data?.url&&/^https:\/\/www\.vfl-ramsdorf\.de\/bomber\/[^/]+\.pdf$/i.test(data.url);
 return <div className="home-shell"><ClubHeader/><main className="bomber-main"><span className="bomber-icon"><Newspaper size={42}/></span><p className="bomber-kicker">Unsere Vereinszeitung</p><h1>Der Bomber</h1><p>Neuigkeiten, Mannschaften und Geschichten aus unserem Verein.</p><section className="bomber-card"><h2>{error||data?.stale?'Zuletzt verfügbare Ausgabe':'Aktuelle Ausgabe'}</h2>{loading&&!data?<p role="status">Ausgabe wird geladen …</p>:<>{(error||data?.stale||!safe)&&<p className="source-warning" role="status">Die aktuelle Ausgabe konnte nicht bestätigt werden. Auf der Vereinswebsite findest du den aktuellen Bomber und das Archiv.</p>}{safe&&<><p>{data.label}</p><a className="bomber-open" href={data.url} target="_blank" rel="noopener noreferrer">{data.stale||error?'Letzte bekannte Ausgabe öffnen':'Bomber lesen'}<ExternalLink size={18}/></a><small>PDF · öffnet in einem neuen Tab</small></>}</>}{error&&<button className="source-retry" onClick={retry}><RefreshCw size={14}/>Erneut laden</button>}</section><a className="home-website" href="https://www.vfl-ramsdorf.de/fussball/stadionzeitung" target="_blank" rel="noopener noreferrer">Alle Ausgaben auf der Vereinswebsite<ExternalLink size={16}/></a></main></div>;
}
