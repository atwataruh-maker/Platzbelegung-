# VfL Ramsdorf · Update auf v0.15.0

Die ZIP enthält alle geänderten Dateien seit v0.13.0 und lässt sich auch auf ältere Stände ab v0.11.x einspielen.

## Änderungen
- **Hallenplan:** Im Reiter „Plätze“ gibt es oben den Umschalter „Plätze | Halle“. Die Hallenansicht zeigt für den gewählten Tag Paulushalle und Walburgishalle mit den Trainingszeiten der Teams und den freien Fußball-Hallenzeiten.
- Gültig vom 19.11.2026 bis 31.03.2027. Platz- und Kabinenbelegung bleiben unverändert.
- Die Hallenzeiten stehen in `server/hallenplan.json` und können dort ohne neuen Build angepasst werden.

## Installation auf STRATO
1. Bestehendes `/app/` sichern.
2. `vfl-update-0.15.0.zip` herunterladen und entpacken.
3. Inhalt in `/app/` übertragen, Ordner zusammenführen: zuerst `server/`, dann `index.php`, dann `assets/`, zuletzt `frontend-dist/index.html`.
4. **config.php, var/, server/training.json und die Datenbank unverändert lassen.** Kein Installer nötig.
5. Browsercache aktualisieren, oben v0.15.0 prüfen. Im Reiter „Plätze“ auf „Halle“ tippen und z. B. Dienstag wählen: Paulushalle mit E1-Juniorinnen 17:00–18:00 und E2-Junioren 18:00–19:30.
