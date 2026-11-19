import {appPath} from '@/frontend/api';
import clubLogo from '@/frontend/assets/vfl-logo.png';
/** Single place for the visible app version (keep in sync with package.json). */
export const APP_VERSION='0.15.0';
export function ClubHeader(){return <header className="vfl-header"><a href={appPath('/')} aria-label="VfL Ramsdorf Startseite"><img src={clubLogo} alt="VfL Ramsdorf e.V. 1936"/><span>VfL Ramsdorf</span></a><span className="vfl-version">v{APP_VERSION}</span></header>;}
