# DFBnet-Spielplan-Import (automatisiert)

Dieses Verzeichnis ist **kein Teil der PHP-Anwendung** und laeuft nicht auf
STRATO. Es ist ein separates Node.js-Skript, das per GitHub Action
(`.github/workflows/dfbnet-import.yml`) taeglich automatisch laeuft, sich
bei DFBnet einloggt, den Spielplan als CSV exportiert und die
VfL-Ramsdorf-Spiele an die App (`POST /import/games`) schickt. Die App
berechnet daraus wie bei manuell eingetragenen Spielen automatisch
Platzsperre, Konflikte und Ausweichvorschlaege.

## Wichtiger Vorbehalt

Dieses Skript wurde **ohne echten Zugriff auf die DFBnet-Oberflaeche**
gebaut (das Test-Netzwerk war beim Bau blockiert). Die Login-Feldnamen und
der Export-Button sind bestmoegliche Annahmen aus oeffentlicher
Dokumentation, keine live geprueften Selektoren. **Vor dem produktiven
Einsatz unbedingt einmal manuell ueber "Run workflow" (workflow_dispatch)
testen** und bei Fehlern die mit `ANPASSEN:` markierten Stellen in
`import.js` an die tatsaechliche Oberflaeche anpassen. Bei jedem Lauf
werden Screenshots als Workflow-Artefakt hochgeladen (auch bei Erfolg) -
damit sieht man ohne eigenen DFBnet-Login sofort, wo es ggf. hakt.

## Einrichtung

1. **Wer hat DFBnet-Zugang?** In der Regel der/die Spielausschuss-Obmann/
   -frau oder Jugendleiter:in des Vereins. Diese Person muss die
   Zugangsdaten bereitstellen.

   **Zugangsdaten werden in der App eingetragen, nicht bei GitHub:** als
   Admin in der Platzbelegung-App unter **DFBnet-Zugang**
   (`/admin/dfbnet`) einmalig Benutzername und Passwort hinterlegen. Das
   Passwort wird dort verschluesselt gespeichert und nie wieder im
   Klartext angezeigt; die Automatisierung ruft es sich bei jedem Lauf
   ueber den tokengesicherten Endpunkt `GET /import/dfbnet-credentials`
   ab (siehe Sicherheitshinweis unten). Es muss also **kein** DFBnet-
   Passwort mehr als GitHub-Secret hinterlegt werden.

2. **team-mapping.json anlegen** (Kopie von `team-mapping.example.json`,
   im Repo einchecken - die Datei enthaelt keine Zugangsdaten, nur
   URLs/Kennungen):
   - Fuer jede Mannschaft, deren Heimspiele automatisch importiert werden
     sollen, einmal manuell bei DFBnet einloggen, zur
     "Spielplanbearbeitung" der jeweiligen Staffel navigieren und die
     Browser-URL kopieren (`spielplanUrl`). Das umgeht das Risiko, dass
     wir die genaue Menuefuehrung falsch nachbauen.
   - `ourTeamExternalRef` muss exakt der "Fussball.de/DFBnet-Kennung"
     entsprechen, die im Adminbereich unter Mannschaften > Bearbeiten
     hinterlegt ist (frei waehlbar, z.B. `vfl1`, `d1junioren`).

3. **In der App:** Fuer jede betroffene Mannschaft im Adminbereich unter
   "Mannschaften" die Felder "Fussball.de/DFBnet-Kennung" (siehe oben) und
   idealerweise "Standard-Heimplatz" setzen, damit importierte Heimspiele
   sofort einen Platz zugewiesen bekommen (sonst muss der Admin den Platz
   nach dem Import manuell in der Spiele-Liste nachtragen).

4. **API-Token erzeugen** und in `config/config.php` unter `import.api_token`
   eintragen (z.B. `php -r "echo bin2hex(random_bytes(32));"`). Dieser Token
   sichert sowohl `POST /import/games` als auch `GET /import/dfbnet-credentials` ab.

5. **DFBnet-Zugangsdaten eintragen**: als Admin in der App unter
   `/admin/dfbnet` (siehe Schritt 1).

6. **GitHub-Secrets** im Repository unter Settings > Secrets and variables >
   Actions anlegen:
   - `IMPORT_API_URL` - z.B. `https://platzbelegung.vfl-ramsdorf.de/import/games`
   - `IMPORT_API_TOKEN` - derselbe Wert wie in `config.php`

7. Workflow einmal manuell ausloesen (Tab "Actions" > "DFBnet
   Spielplan-Import" > "Run workflow") und die Screenshots/Logs pruefen.

## Lokal testen

```bash
cd automation/dfbnet-import
npm install
npx playwright install chromium
export IMPORT_API_URL=... IMPORT_API_TOKEN=...
npm run import
```

Die DFBnet-Zugangsdaten holt sich das Skript dabei live von der App (siehe
oben) - lokal muss also eine erreichbare Instanz mit bereits unter
`/admin/dfbnet` hinterlegten Zugangsdaten als `IMPORT_API_URL` angegeben werden.

## Sicherheitshinweis

Das DFBnet-Passwort landet verschluesselt in der Datenbank der App (siehe
`App\Core\Crypto`, Schluessel in `config/config.php` unter
`security.encryption_key`) und wird nur fuer den unmittelbaren Login-
Vorgang der Automatisierung entschluesselt an sie uebertragen - nie an
Dritte, nie im Klartext geloggt. Automatisierter Login kann je nach
DFBnet-Nutzungsbedingungen unerwuenscht sein und im ungluecklichen Fall zu
einer Kontosperrung fuehren - das ist eine Risikoabwaegung, die der Verein
bewusst getroffen hat. Falls die Kontosperrung passiert oder DFBnet den
automatisierten Zugriff technisch unterbindet (z.B. Captcha), bleibt der
CSV-Upload/die manuelle Spieleingabe in der App als Fallback vollstaendig
funktionsfaehig.
