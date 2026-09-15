# fussball.de-Anbindung (aktuell: nur Diagnose)

Dieses Verzeichnis enthaelt **noch keinen produktiven Import**, sondern nur
`diagnose.js` - einen rein lesenden Testabruf fuer drei recherchierte
fussball.de-Endpunkte (Vereinsspielplan sowie die Mannschaftsspielplaene von
E1 und E2, siehe `TARGETS` in der Datei). Hintergrund: fussball.de zeigt den
Spielplan des VfL Ramsdorf oeffentlich ohne Login an - das waere eine
einfachere und risikoaermere Datenquelle als der DFBnet-Import unter
`automation/dfbnet-import` (kein automatisierter Login, keine
Kontosperrungs-Gefahr). Ob und wie zuverlaessig sich die Daten so tatsaechlich
abrufen lassen, ist aber noch nicht bestaetigt.

## Was `diagnose.js` macht

Fuer jeden Endpunkt: HTTP-Abruf per `fetch()` (kein Headless-Browser, kein
Login), und Speicherung von HTTP-Status, finaler URL, Content-Type sowie dem
rohen HTML in `diagnose-output/` (lokal ignoriert, siehe `.gitignore`) - plus
`summary.json` mit allen Ergebnissen. Veraendert nichts an der App, ruft
nicht `/import/games` auf.

## Ausfuehren

- **Ueber GitHub Actions** (empfohlen, da das lokale Sandbox-Netz beim Bauen
  dieses Skripts fussball.de blockiert hat und dieser Zustand pruefbedürftig
  ist): Tab "Actions" > "fussball.de Diagnose-Abruf" > "Run workflow". Die
  gespeicherten HTML-Dateien landen als Workflow-Artefakt zum Download.
- **Lokal**: `node diagnose.js` (Node 18+, `fetch` ist eingebaut).

## Naechste Schritte (erst nach erfolgreicher Diagnose)

1. Gespeicherte HTML-Dateien pruefen: enthalten sie echte Spieltermine oder
   nur eine leere JavaScript-Huelle? Falls Zweiteres: doch Playwright noetig
   (wie bei DFBnet).
2. Pagination ("Mehr laden") und Filterparameter der Vereinsspielplan-Seite
   klaeren.
3. Erst danach: echten Import bauen, analog zu `automation/dfbnet-import`
   (Zuordnung ueber Team-IDs statt Namen, Heim/Gast unterscheiden,
   "spielfrei"-Eintraege ausschliessen, bei fehlgeschlagenem/unvollstaendigem
   Abruf bestehende Buchungen unveraendert lassen).

## Rechtlicher Hinweis

Die genutzten Endpunkte sind auf fussball.de oeffentlich verlinkt, aber keine
dokumentierte, offiziell unterstuetzte API. Vor einem produktiven, dauerhaften
Einsatz sollte der Verein pruefen (ggf. bei DFB/fussball.de nachfragen), ob
die automatisierte Weiterverwendung dieser Daten zulaessig ist.
