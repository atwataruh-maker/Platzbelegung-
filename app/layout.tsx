import type { Metadata, Viewport } from 'next';
import './globals.css';
export const metadata: Metadata = {
 title: 'Platzbelegung · VfL Ramsdorf', description: 'Der Trainings- und Platzbelegungsplan des VfL Ramsdorf, direkt von der Vereinswebsite.',
 manifest: '/manifest.webmanifest', icons: { icon: '/favicon.svg', apple: '/apple-touch-icon.png' },
 appleWebApp: { capable: true, statusBarStyle: 'default', title: 'VfL Plätze' },
};
export const viewport: Viewport = { width: 'device-width', initialScale: 1, themeColor: '#123daa' };
export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) { return <html lang="de"><body>{children}</body></html>; }
