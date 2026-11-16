# VfL Ramsdorf · Update auf v0.14.0

Die ZIP enthält den vollständigen Breitensport-Stand (auch für Installationen ab v0.11.x).

## Änderungen
- Breitensport-Zeiten werden **stündlich mit der Vereinswebsite abgeglichen** (Detailseiten, Kurstexte, Wochenplan-Übersicht für Leichtathletik).
- Ändert der Verein auf der Website eine Zeit, einen Ort oder die Übungsleitung, übernimmt die App das automatisch und zeigt „Laut Website geändert · bisher …“.
- Termine, die auf der Website nicht mehr zu finden sind, bleiben sichtbar und erhalten den Hinweis „Auf der Website nicht mehr gefunden“.
- Ist die Website nicht erreichbar, gelten die hinterlegten Zeiten aus `server/breitensport.json`. Am Ende des Wochenplans steht der Zeitpunkt des letzten Abgleichs.

## Installation auf STRATO
1. Bestehendes `/app/` sichern.
2. `vfl-update-0.14.0.zip` herunterladen und entpacken.
3. Inhalt in `/app/` übertragen, Ordner zusammenführen: zuerst `server/`, dann `index.php`, dann `assets/`, zuletzt `frontend-dist/index.html`.
4. **config.php, var/, server/training.json und die Datenbank unverändert lassen.** Kein Installer nötig.
5. Browsercache aktualisieren, oben v0.14.0 prüfen. Im Reiter „Sport“ steht unten „Zeiten mit der Vereinswebsite abgeglichen: …“.

Der erste Aufruf nach dem Update lädt die Breitensport-Seiten der Website und kann einige Sekunden dauern; danach kommen die Daten aus dem Zwischenspeicher (Zeiten 60 Minuten, Kurse 30 Minuten).

Sportabzeichen-Termine (nur als Bild auf der Website) und einmalige Veranstaltungen werden weiterhin in `server/breitensport.json` gepflegt.
