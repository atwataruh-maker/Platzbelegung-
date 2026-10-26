# Update auf 0.10.3: Nordvelen und kompakte Kabinenbelegung

SF Nordvelen nutzt unseren Platz mit. Für das reguläre Training donnerstags 19:00–20:30 ist die **1. Auswärtskabine von 18:45–20:45 allein** vorgesehen. Das unbekannte Geschlecht wird nicht geraten; Nordvelen wird grundsätzlich nicht mit anderen Teams in eine Kabine gelegt.

Benötigt ein Spielgast die Auswärtskabine, hat er Vorrang. Nordvelen bekommt nach Möglichkeit eine andere freie Kabine. Konkrete Abweichungen werden oben mit Mannschaft, Zeit und Kabinenwechsel angezeigt. Falls keine Kabine zugeteilt werden kann, bleibt der Konflikt sichtbar. Eine tatsächliche Personenkapazität je Kabine ist weiterhin nicht hinterlegt; die Prüfungen betreffen Zeiten, alleinige Nutzung und zulässiges Teilen.

Die normalen Kabinenkarten zeigen Team, Uhrzeit und Aktivität. Alle allgemeinen Regeln stehen gesammelt unten unter **Regeln zur Kabinenbelegung**. Wiederholte Begründungen und der allgemeine Mittwochs-Hinweis entfallen. Nur tatsächliche Abweichungen, offene Zuordnungen oder ein fehlgeschlagener Spielabgleich erzeugen Hinweise oberhalb des Plans.

## Installation durch den Webseiten-Admin

1. App-Dateien und Datenbank sichern.
2. `vfl-update-0.10.3.zip` herunterladen und lokal entpacken.
3. Inhalt der Ordner `server`, `assets` und `frontend-dist` in das bestehende Webspace-Verzeichnis `/app/` hochladen. Neue Assets zuerst, `frontend-dist/index.html` zuletzt übertragen. Gleichnamige Dateien ersetzen.
4. **config.php, var/, server/training.json und Datenbank erhalten.** Kein Installer, keine Datenbankänderung erforderlich.
5. App neu laden: oben muss **Version 0.10.3** stehen.
6. Kabinenbelegung am Donnerstag, 29.10. bzw. 05.11. prüfen. Ohne zusätzliche Heimspiele steht Nordvelen in der 1. Auswärtskabine; am Mittwoch gilt weiterhin der vereinbarte Tausch für die Mädchen und Jungen.

Das Paket enthält auch die Verbesserungen aus 0.10.1 und 0.10.2. Es kann auf Version 0.10, 0.10.1 oder 0.10.2 eingespielt werden. Die Veröffentlichung auf GitHub aktualisiert den Webspace nicht automatisch.
