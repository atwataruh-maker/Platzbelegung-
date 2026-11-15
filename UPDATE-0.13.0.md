# VfL Ramsdorf · Update auf v0.13.0

## Änderungen
- Neuer Reiter **„Sport“** mit allen Breitensport-Angeboten: Wochenplan Mo–Sa mit Zielgruppenfilter, buchbare Kurse mit freien Plätzen und eine Seite je Angebot mit Zeiten, Übungsleitung, Ort und Kalenderexport.
- Die Startseite bleibt unverändert; nur die Leiste unten hat einen sechsten Reiter.
- Kursdaten werden von `www.vfl-ramsdorf.de/breitensport/kurse` gelesen. Gebucht wird weiterhin auf der Vereinswebsite.
- Feste Angebote und Ansprechpartner stehen in `server/breitensport.json`.
- Platz- und Kabinenbelegung, Helferbörse und Datenbank sind unverändert.

## Installation auf STRATO
1. Bestehendes `/app/` sichern.
2. `vfl-update-0.13.0.zip` herunterladen und entpacken.
3. **Inhalt** der ZIP in das bestehende STRATO-Verzeichnis `/app/` übertragen, Ordner zusammenführen, nicht löschen. Reihenfolge:
   1. `server/Breitensport.php`, `server/breitensport.json`, `server/Core.php`, `server/bootstrap.php`
   2. `index.php`
   3. Ordner `assets/`
   4. zuletzt `frontend-dist/index.html`
4. **config.php, var/, server/training.json und die Datenbank unverändert lassen.** Kein Installer nötig.
5. Browsercache aktualisieren und oben v0.13.0 prüfen. Reiter „Sport“ öffnen, einen Wochentag und „Kurse“ wählen. Bei den Kursen sollten freie Plätze und „Stand …“ erscheinen.

Der Server muss ausgehende HTTPS-Verbindungen zu `www.vfl-ramsdorf.de` aufbauen dürfen (wie bereits für den Bomber). Sind die Kursdaten nicht erreichbar, zeigt die App einen Hinweis und den Wochenplan weiterhin an.

Angebote ändern: `server/breitensport.json` auf dem Server bearbeiten (gültiges JSON, Wochentage 1 = Montag bis 6 = Samstag). Kein neuer Build nötig.

Bei Problemen die gesicherte vorherige Fassung wiederherstellen. GitHub aktualisiert STRATO nicht automatisch.
