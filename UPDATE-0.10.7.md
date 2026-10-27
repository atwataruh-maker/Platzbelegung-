# Update auf 0.10.7: Heute beim VfL

Die Hauptseite übernimmt das freigegebene Design mit drei fotografischen Bildbannern und dem echten Vereinslogo. Die Banner wechseln automatisch alle **5 Sekunden**:

1. Heutige Spiele aller Mannschaften, Heim und Auswärts, mit Uhrzeit, Status und verfügbaren Ergebnissen. Bis zu zwei Spiele direkt sichtbar, alle weiteren per Direktlink.
2. Heutige Platzverschiebungen mit Mannschaft, Zeit, bisherigem/neuem Platz und Grund.
3. Heutige Kabinenverschiebungen mit Mannschaft, Zeit, bisheriger/neuer Kabine und Grund.

Ohne Änderungen wird die reguläre Belegung bestätigt, wenn der Spielabgleich vollständig war. Leere Spieltage erhalten „Heute keine Spiele“. Bei fehlenden, veralteten oder unvollständigen Daten erscheint ein entsprechender Hinweis statt einer falschen Entwarnung. Offene Zuordnungen haben Vorrang vor normalen Änderungen.

Bedienung: Wischen, drei Auswahlpunkte, vor/zurück und Pause. Wischen pausiert; bei Tastaturfokus, Mauszeiger im Banner oder verborgenem Browserfenster hält der Wechsel ebenfalls an. Bei reduzierter Bewegung startet er pausiert. Tagesdaten werden bei sichtbarer Seite jede Minute neu angefragt (die vorhandenen Server-Caches gelten weiter), der Tag folgt Europe/Berlin. Die drei Kategorien bleiben stets erhalten.

Alle vorhandenen Belegungsregeln aus 0.10.6 bleiben bestehen. Die Motive sind generierte Symbolbilder, keine Aufnahmen der tatsächlichen VfL-Anlagen. Keine Beispielspiele oder Beispielverschiebungen werden im Betrieb verwendet.

## Prüfung

209 PHP/TypeScript-Vergleiche und die bestehenden Ergebnis-/Sitzungstests erfolgreich. Neue Tests prüfen Bannerzustände und die PHP-Tagesabfrage. Browserprüfung erfolgreich: 5-Sekunden-Wechsel, Pause, Wischen, Direktlinks, 320–1280 Pixel Breite, Bild-/Logoladung, Quellenfehler, reduzierte Bewegung und Berliner Datumswechsel. Die echte STRATO-Installation wird durch diese lokalen Tests nicht aktualisiert.

## Installation durch den Webseiten-Admin

1. App-Dateien und Datenbank sichern.
2. `vfl-update-0.10.7.zip` entpacken.
3. **Neue Assets zuerst** nach `/app/assets/` hochladen. Danach `server/` und **index.php** ersetzen; die neue API `/app/api/heute` benötigt diese index.php. Zuletzt `frontend-dist/index.html` übertragen.
4. **config.php, var/, server/training.json und Datenbank erhalten.** Kein Installer oder Datenbankumbau erforderlich.
5. Startseite neu laden, Version 0.10.7 prüfen. Der Banner soll nach fünf Sekunden wechseln; Pause und die drei Direktlinks testen.
6. Bei „Abgleich derzeit nicht möglich“ zuerst sicherstellen, dass die neue index.php hochgeladen wurde. Datenquellenfehler werden weiterhin im PHP-Serverprotokoll festgehalten.

Das Paket ist kumulativ für 0.10 bis 0.10.6. GitHub aktualisiert STRATO nicht automatisch.
