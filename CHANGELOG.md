# Änderungen

## v0.10 — 21.10.2026 · PHP/STRATO

- PHP-Backend für Spiele, Ergebnisse sowie Platz- und Kabinenverteilung.
- MySQL/MariaDB für Helfergesuche; eigener Admin-Login mit Session, Passwort-Hashing, CSRF-Schutz und Anmeldebegrenzung.
- Mobile React-Oberfläche als fertige Browser-Dateien; kein Node.js oder Cloudflare auf dem Zielserver.
- Einmaliger gesicherter Web-Installer, Unterstützung für Subdomains und Unterverzeichnisse.
- Fertiges upload/-Paket und deutsche Installationsanleitung.
- 140 PHP/TypeScript-Vergleichstests sowie HTTP-/MariaDB-Tests für Installation, Anmeldung und Gesuche.
- Live-Abruf geprüft: 19 Spiele, davon 11 lesbare Ergebnisse (15.–21.10.2026); Unterverzeichnis-Routing und Cache geprüft.
- Kein Live-Deployment auf STRATO; Abnahme im konkreten Hosting-Paket und auf Mobilgeräten bleibt erforderlich.


## v0.9 — 17.10.2026

- Vollständigen Stand der mobilen VfL-Ramsdorf-Vereinsapp für GitHub bereitgestellt.
- Eigene Startseiten-Kacheln und Seiten für Plätze, Kabinen und Ehrenamt.
- Sommerplan mit 38 wiederkehrenden Trainingsgruppen und Original-PDF.
- Spiele und Ergebnisse mit Datumswahl; automatische Platzverteilung und Konflikthinweise.
- Zusammenhängende Spielkacheln über beide Platzhälften mit Mannschaftslogos.
- Zwei Hauptplatzhälften für Ausweichtraining der 1. Herren und Jugend.
- Vier Kabinen mit festen Heimkabinen für die 1. und 2. Herren.
- Gastvorrang, freie Auswärtskabinen für Heimteams und gleichgeschlechtliche gemeinsame Nutzung für alle Jugendteams von G bis A.
- Damen ausschließlich allein in der 1. oder 2. Heimkabine; Konflikte bei fehlender Verfügbarkeit werden angezeigt.
- Umkleidepuffer und gekennzeichnete gemeinsame Belegungen.
- Regressionen für den 15. und 16.10.2026 geprüft; unklare Geschlechtszuordnung von SF Nordvelen bleibt offen.
- Versionsanzeige auf allen Hauptseiten auf 0.9 angehoben.

### Prüfstand

Zuordnungsregeln für Plätze und Kabinen, Geschlechter- und Altersgrenzen, Zeitüberschneidungen und Gastvorrang sind automatisiert geprüft. TypeScript-Prüfung und Produktionsbuild erfolgreich. Keine visuelle Prüfung auf einem realen iPhone oder Android-Gerät.
