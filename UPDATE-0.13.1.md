# VfL Ramsdorf · Update auf v0.13.1

Setzt **v0.13.0** voraus. Wer noch v0.11.x nutzt, installiert zuerst [Update 0.13.0](UPDATE-0.13.0.md) oder überträgt direkt alle Dateien aus dieser ZIP (sie enthält den vollständigen Stand von v0.13.0 und v0.13.1).

## Änderungen
- Sportabzeichen nur an den veröffentlichten Abnahmeterminen 2026, nicht mehr jede Woche. Vergangene Termine sind durchgestrichen; Ort Wulfkampstadion ergänzt.
- „Nächste Termine“ im Reiter Sport: Übergabefeier Sportabzeichen am 22.11.2026 ab 15 Uhr in der Ramsdorfer Burg.
- Aqua Fitness ergänzt (donnerstags 17:15–17:45 Uhr, Freibad Ramsdorf, Mai–September).
- Leichtathletik: Ansprechpartnerin, max. 10 Kinder je Gruppe, Anmeldung erforderlich.

## Installation auf STRATO
1. Bestehendes `/app/` sichern.
2. `vfl-update-0.13.1.zip` herunterladen und entpacken.
3. Inhalt in `/app/` übertragen, Ordner zusammenführen: zuerst `server/`, dann `index.php`, dann `assets/`, zuletzt `frontend-dist/index.html`.
4. **config.php, var/, server/training.json und die Datenbank unverändert lassen.** Kein Installer nötig.
5. Browsercache aktualisieren, oben v0.13.1 prüfen, im Reiter „Sport“ die „Nächsten Termine“ und die Seite „Sportabzeichen“ ansehen.

Neue Sportabzeichen-Termine stehen auf der Website nur als Bild. Sie werden in `server/breitensport.json` beim Angebot `sportabzeichen` unter `dates` (Format JJJJ-MM-TT) bzw. `events` eingetragen. Kein neuer Build nötig.
