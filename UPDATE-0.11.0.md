# Update auf 0.11.0 · Neue Startseite, Ergebnisse und Bomber

## Änderungen

- Neue Startseite nach dem freigegebenen Entwurf: VfL-Blau/Weiß, echtes Vereinslogo und dezentes Wappen im Hintergrund. Kein Slogan und keine doppelten Schaltflächen „Platz finden“/„Kabine finden“.
- „Heute wichtig“ zeigt Platz- und Kabinenhinweise mit Grund und datumsgebundenem Absprung. Reguläre Belegung wird nur bei vollständig bestätigtem Spielabgleich angezeigt. Der bisherige Fotobanner entfällt.
- Heutige Spiele und darunter alle Spiele mit Anstoß in den letzten **48 verstrichenen Stunden**, neueste zuerst. Nicht nur „gestern und heute“; Sommer-/Winterzeit wird berücksichtigt. Fehlende, vorläufige, bestätigte und ältere Spielstände werden unterschieden. Spiele ohne gemeldetes Ergebnis bleiben mit Hinweis sichtbar. Absagen/Verlegungen behalten ihren Status.
- Die Auswahl „Meine Mannschaft“ filtert heutige Spiele und letzte Ergebnisse anhand der in diesem Zeitraum vorhandenen Mannschaften. Die Belegungshinweise bleiben ausdrücklich vereinsweit sichtbar.
- Fester Schnellzugriff auf allen Seiten: **Heute · Plätze · Kabinen · Helfen · Bomber**. „Bomber“ ist der letzte Reiter, keine zusätzliche große Startseitenkachel.
- Bomber: PHP liest die derzeit ausgewählte PDF-Ausgabe aus dem PDF-Viewer auf der offiziellen Stadionzeitungsseite. Neue Ausgaben werden spätestens beim nächsten Abruf nach 15 Minuten Cachezeit erkannt. PDF öffnet in einem neuen Tab, das Archiv bleibt direkt erreichbar. Bei Quellenfehlern wird eine ältere Ausgabe als solche gekennzeichnet oder auf die Vereinswebsite verwiesen.
- Keine Änderung an Platz-/Kabinenregeln, Trainingsplan, Helfergesuchen, Konten oder Datenbank. Keine Beispieldaten im Produkt.

## Für den Webseiten-Admin

1. Bestehendes `/app/` und die Datenbank sichern.
2. PHP 8.2 oder neuer verwenden. Zusätzlich zu den bisherigen Erweiterungen muss **DOM/XML** aktiviert sein (für das Lesen der Bomber-Seite).
3. `vfl-update-0.11.0.zip` entpacken und dessen Inhalt in das bestehende `/app/` übertragen: **assets/** zuerst, dann **server/** und **index.php**, zuletzt **frontend-dist/index.html**. Ordner zusammenführen, nicht vorab leeren.
4. **config.php, var/, server/training.json und Datenbank unverändert lassen.** Sie sind nicht Bestandteil des Update-ZIPs. Kein Installer, kein Datenbankumbau, kein Node.js auf STRATO nötig.
5. Startseite neu laden und **v0.11.0** prüfen. „Bomber“ öffnen, PDF-Link testen, Plätze/Kabinen aufrufen und Spielergebnisse prüfen. Die neuen Endpunkte heißen `/app/api/ergebnisse` und `/app/api/bomber`; deshalb unbedingt index.php und alle mitgelieferten PHP-Dateien ersetzen.
6. Bei Problemen die gesicherten App-Dateien wiederherstellen. Alte Assets dürfen während des Updates bestehen bleiben.

**GitHub aktualisiert STRATO nicht automatisch.** Dieses Paket ist ein Update für die bestehende PHP-App, keine neue APK. Eine neue Bomber-Ausgabe braucht künftig kein App-Update.

## Prüfungen

- TypeScript-Prüfung und Produktionsbuild.
- Bestehende 209 PHP/TypeScript-Vergleiche sowie Ergebnisquellen-, Cache- und Sitzungsprüfungen.
- Tests für 48-Stunden-Grenzen, Zeitumstellung, Ergebniszustände, Mannschaftsfilter, Bomber-Auswahl und URL-Beschränkungen.
- HTTP-Tests unter `/app/` einschließlich neuer APIs und Bomber-Route.
- Browserprüfung mit echten Daten: Startseite, Mannschaftsfilter und Bomber-Reiter; Smartphone-Layouts mit 320 und 390 Pixeln Breite.
- Echter Quellenabruf am 14.11.2026: aktuelle Bomber-PDF und mehrere bestätigte Fußball.de-Ergebnisse erfolgreich geladen. Das garantiert nicht die dauerhafte Verfügbarkeit der externen Quellen; Fehler werden sichtbar ausgewiesen.
