import {appPath} from '@/frontend/api';
import clubLogo from '@/frontend/assets/vfl-logo.png';
export function ClubHeader(){return <header className="vfl-header"><a href={appPath('/')} aria-label="VfL Ramsdorf Startseite"><img src={clubLogo} alt="VfL Ramsdorf e.V. 1936"/><span>VfL Ramsdorf</span></a><span className="vfl-version">v0.11.1</span></header>;}
