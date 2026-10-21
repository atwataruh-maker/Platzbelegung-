# Update 0.10 → 0.10.1 für https://vfl-ramsdorf.de/app/

1. Vorhandene App-Dateien und Datenbank sichern.
2. `vfl-update-0.10.1.zip` herunterladen und lokal entpacken.
3. Den **Inhalt** (Ordner `assets`, `frontend-dist`, `server`) in das vorhandene Webspace-Verzeichnis `/app/` hochladen und gleichnamige Dateien ersetzen. Die neuen Asset-Dateien zuerst, `frontend-dist/index.html` zuletzt übertragen.
4. **config.php, var/, Datenbank und server/training.json erhalten.** Der Installer wird nicht benötigt. Das Update enthält weder Zugangsdaten noch eine neue Datenbank.
5. App neu laden. Oben muss **Version 0.10.1** stehen.
6. Auf „Ehrenamt & Helfen“ → „Anmelden & Gesuch erstellen“ gehen. Mit dem beim Installer angelegten Admin-Konto anmelden, danach „Gesuche verwalten“ öffnen. Ein Testgesuch erstellen, abschließen und wieder öffnen.
7. Bei den Spielen den 18.10.2026 wählen. Im Entwicklungstest waren 4 Ergebnisse lesbar. Der laufende Abruf bleibt von FUSSBALL.DE und den ausgehenden Verbindungen des Hostings abhängig. Unlesbare Ergebnisse werden nach 30 Sekunden erneut geprüft.

## Falls die Helferliste danach nicht bearbeitbar ist

- Öffentliche Besucher können Gesuche lesen. Erstellen und Abschließen bleibt angemeldeten Vereins-Admins vorbehalten; es gibt keine öffentliche Registrierung.
- Die Zugangsdaten stammen aus der Installation, nicht von GitHub oder ChatGPT. Bei fehlenden Zugangsdaten den Webseiten-Admin kontaktieren.
- Wenn nach Anmeldung kein „Helfer suchen“ erscheint oder Speichern scheitert: genaue Fehlermeldung und Zeitpunkt notieren. Der Admin prüft das PHP-Fehlerprotokoll und `app_url` in config.php (hier exakt `https://vfl-ramsdorf.de/app`). Keine Passwörter oder config.php weitergeben.
- Der öffentliche Abruf der leeren Helferliste funktionierte bei der Prüfung. Ein produktiver Schreibtest wurde ohne Admin-Anmeldung nicht durchgeführt.

## Ergebnisabruf

Schriftdateien werden für die entzifferten Ergebnisse benötigt. Bei vorübergehenden Netzwerkfehlern gibt es einen begrenzten Wiederholungsversuch; der Quellplan und seine Schriftkennung bleiben für weitere Versuche zusammen gespeichert. Bei anhaltenden Fehlern findet der Admin im PHP-Protokoll Einträge mit `VfL score font`. 403/429 werden nicht unmittelbar wiederholt. TLS-Prüfung bleibt aktiv.

Bekannte Ergebnisse erscheinen bei Fehlern als „Letzter Stand“ mit Abrufzeit. Sie werden nicht in verlegte, abgesagte oder anders besetzte Spiele übernommen. Nach 24 Stunden werden sie verworfen. Es werden keine Ergebnisse geraten.
