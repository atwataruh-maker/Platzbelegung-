# VfL Ramsdorf · Update auf v0.11.1

## Änderungen
- Heim- und Gastmannschaften erhalten ihre Vereinslogos in den Bereichen „Heutige Spiele“ und „Ergebnisse“. Das lokale VfL-Wappen wird unabhängig vom externen Spielplan geladen; für andere Vereine wird das in der Quelle hinterlegte Logo verwendet. Bei nicht verfügbarem Logo erscheinen Initialen.
- Die gelben allgemeinen Quellenhinweise und die wiederholten Texte wie „Bestätigtes Ergebnis“ sind auf der Startseite entfernt.
- Konkrete Hinweise wie „Verlegt“ oder „Abgesetzt“ bleiben sichtbar. Vorläufige/ältere Ergebnisse werden am Spielstand dezent mit ≈ gekennzeichnet; fehlende Daten werden nicht als 0:0 dargestellt.
- Alle übrigen Funktionen und Belegungsregeln bleiben erhalten.

## Installation auf STRATO
1. Bestehendes `/app/` sichern.
2. `vfl-update-0.11.1.zip` herunterladen und entpacken.
3. **Inhalt** der ZIP in das bestehende STRATO-Verzeichnis `/app/` übertragen: zuerst den Ordner `assets/`, anschließend `frontend-dist/index.html`. Ordner zusammenführen, nicht löschen.
4. **config.php, var/, server/training.json, Datenbank und PHP-Dateien unverändert lassen.** Kein Installer nötig.
5. Browsercache aktualisieren und oben v0.11.1 prüfen. Heutige Spiele und Ergebnisse auf dem Smartphone ansehen. Ein fehlendes Gastlogo sollte durch Initialen ersetzt werden.

Bei Problemen die gesicherte vorherige Fassung wiederherstellen. GitHub aktualisiert STRATO nicht automatisch. Externe Vereinslogos hängen von ihrer Quelle ab.
