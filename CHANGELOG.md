# Änderungen

## v0.10.4 — 26.10.2026 · Nordvelen

- Einheitlicher Name „Nordvelen“ in Platz- und Kabinenbelegung.
- Eine Mannschaft mit 10 Spielern hinterlegt und kompakt angezeigt.
- Bestehende Trainingsdateien mit altem Namen bleiben kompatibel. Kabinenregeln unverändert.

## v0.10.3 — 26.10.2026 · Nordvelen und übersichtliche Kabinenansicht

- SF Nordvelen als Platzmitnutzer: im regulären Donnerstagsplan 18:45–20:45 Uhr allein in der 1. Auswärtskabine. Keine Annahme zu Geschlecht oder Altersklasse erforderlich.
- Gastteams von Spielen behalten Vorrang in der 1. Auswärtskabine. Nordvelen weicht bei Bedarf auf eine andere freie Kabine aus und teilt grundsätzlich nicht mit anderen Teams.
- Konkrete Kabinenverschiebungen durch Spiele werden gegenüber dem regulären Trainingsplan ermittelt und oben mit Zeit, alter und neuer Kabine angezeigt.
- Allgemeine Regeln gesammelt unten im aufklappbaren Bereich. Wiederholte Erklärungen, Erfolgsbanner und das allgemeine Mittwochs-Banner entfernt; Konflikte und ein unvollständiger Spielabgleich bleiben erkennbar.
- 170 PHP/TypeScript-Vergleiche sowie Tests zum Ergebnisabruf und zu Sitzungstoken erfolgreich.
- Update-Paket kumulativ für Version 0.10 bis 0.10.2.

## v0.10.2 — 23.10.2026 · Mittwochsbelegung

- Wiederkehrend ab Mittwoch, 28.10.: C- und D1-Juniorinnen bevorzugt in die große 1. Heimkabine, D1- und E2-Junioren in die 3. Heimkabine.
- Anschlussbelegungen werden mitberechnet: Alte Herren im regulären Mittwochsplan ab 19:15 in der 1. Heimkabine.
- Feste Herrenbelegungen, alleinige Damennutzung und Geschlechtertrennung bleiben verbindlich. Bei nötigen Abweichungen erscheint ein Hinweis im Kabinenplan.
- Vor dem 28.10. und an anderen Wochentagen keine neue Sonderregel.
- 158 PHP/TypeScript-Vergleiche einschließlich mehrerer Mittwoche und zusätzlicher Herren-/Damenbelegungen erfolgreich.

## v0.10.1 — 21.10.2026 · Helferzugang und Ergebnisabruf

- Sichtbarer Anmeldezugang direkt auf der Helferseite; Admin-Rechte bleiben erforderlich.
- CSRF-Token vor Schreibaktionen erneuern, ohne Gesuche automatisch doppelt abzusenden.
- Ergebnis-Schriften bei vorübergehenden Netzwerkfehlern einmal erneut abrufen; Fehler ins Serverprotokoll schreiben.
- Quell-HTML separat puffern, damit ein fehlgeschlagener Schriftabruf mit derselben Schriftkennung wiederholt wird.
- Unvollständige Ergebnisse nach 30 Sekunden erneut prüfen; erfolgreiche Abrufe weiterhin 5 Minuten puffern.
- Bei vorübergehenden Fehlern vorhandene Spielstände maximal 24 Stunden als „Letzter Stand“ mit Abrufzeit anzeigen. Keine Übernahme für verlegte, abgesagte oder geänderte Spiele.
- 140 Regelvergleiche plus Tests zu Ausfällen, Wiederholungen, Cache und CSRF bestanden. Live-Test: alle vier Ergebnisse vom 18.10. lesbar.
- Produktives Anlegen von Gesuchen ohne Admin-Zugang nicht geprüft; Update muss auf dem Webspace installiert werden.

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
