# VfL Ramsdorf Vereins-App · v0.9

Mobile Vereins-Webapp mit Platzbelegung, Kabinenbelegung, Fußballspielen und Helferbörse.

## Funktionen

- Startseite mit eigenen Einstiegen für Platzbelegung, Kabinenbelegung und Ehrenamt sowie Link zur Vereinswebsite.
- Aktueller Sommer-Belegungsplan als strukturierter Tagesplan; die Original-PDF liegt unter `public/belegungsplan-sommer-2026.pdf`.
- Heimspiele, Ergebnisse und Tagesnavigation aus FUSSBALL.DE; automatische Ausweichplanung bei Platzkonflikten.
- Hauptplatz mit zwei Trainingshälften als letzte Ausweichoption für die 1. Herren und Jugendteams.
- Vier Kabinen mit festen Zuordnungen für die 1. und 2. Herren, Geschlechtertrennung, Gästen und Umkleidepuffern.
- Freie Auswärtskabinen für Heimteams; gleichgeschlechtliche G- bis D-Jugendteams dürfen Kabinen teilen.
- Ehrenamtliche Gesuche anlegen, abschließen und wieder öffnen; Schreibzugriff für berechtigte Betreiber.

## Technik und Start

React/TypeScript mit Vinext/Vite und Cloudflare Worker. Persistente Helfergesuche verwenden Cloudflare D1; Schema und Migrationen sind enthalten.

Voraussetzung: Node.js ab 22.13.0 und npm.

```sh
npm run install:ci
npm run dev
```

Build: `npm run build`. Details zu Entwicklungsprofilen, Datenbankmigrationen und der Sites-Anmeldung stehen in [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md).

Die gehostete App verwendet die Sites-Anmeldung und deren Zugriffskontrolle. Der Quellcode enthält keine Produktionsdatenbank oder Zugangsdaten. Ein GitHub-Upload richtet kein neues Hosting und keine Datenbank ein. Dieses Projekt ist eine Webapp; eine native APK oder iOS-App ist nicht enthalten.

## Prüfungen

```sh
node scripts/check-allocation.mjs
node scripts/check-cabins.mjs
node scripts/check-pitch-layout.mjs
node scripts/check-match-days.mjs
node scripts/check-helpers.mjs
npx tsc --noEmit --incremental false
```

Die Regeln zur Platz- und Kabinenverteilung liegen in `lib/pitch-allocation.ts` und `lib/cabin-allocation.ts`; der Grundplan in `lib/training-plan.ts`. Spiel- und Umkleidezeiten sind dokumentierte Planungsannahmen. Bei fehlenden oder unklaren Quellenangaben zeigt die App Hinweise zur Abstimmung.

## Version und Veröffentlichung

Aktueller Stand: **v0.9 (0.9.0), 17.10.2026**. Änderungen: [CHANGELOG.md](CHANGELOG.md).

GitHub-Branch: `vereinsapp-v0.8` (der beim Export vereinbarte Branchname; die enthaltene App ist v0.9).

Die separat entwickelte PHP-Anwendung liegt weiterhin auf dem Branch `main` dieses GitHub-Repositories.

Gehostete App: https://vfl-ramsdorf-platzbelegung.afwataruh.chatgpt.site
