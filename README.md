# VfL Ramsdorf Vereins-App · v0.10.4 · PHP/STRATO

**Branch `strato-php`: eigenständig auf PHP-Webhosting betreibbare Vereins-App.**

Platzbelegung, Kabinenbelegung, Spiele & Ergebnisse, Helferbörse und eigener Admin-Login. Mobile Oberfläche aus v0.9 übernommen. PHP verarbeitet Spiele und berechnet die Platz- und Kabinenbelegung. Gesuche werden in MySQL/MariaDB gespeichert.

## Direkt installieren

**[Installationsanleitung](INSTALLATION.md)** lesen. Das Verzeichnis **[upload/](upload/)** enthält das fertige Paket einschließlich JavaScript/CSS. Nur dessen Inhalt in ein neues Webverzeichnis hochladen, config.php einrichten und den einmaligen Installer ausführen. Node.js, Composer, Cloudflare und ChatGPT sind auf dem Zielserver nicht nötig.

Voraussetzung: PHP >=8.2 mit curl, mbstring, PDO/pdo_mysql, MySQL 8 oder MariaDB >=10.6, HTTPS und Apache/.htaccess. Der konkrete STRATO-Tarif und die Serverkonfiguration müssen beim Betreiber geprüft werden. Kein Live-Upload auf STRATO wurde durchgeführt.

## Regeln

- Spielbedingte Ausweichplanung und zusammenhängende Kacheln für ganze Plätze.
- Hauptplatz als letzte Trainingsalternative für 1. Herren und Jugend, zwei Hälften.
- 1. Herren exklusiv Heimkabine 1; 2. Herren exklusiv Heimkabine 2.
- Damen ausschließlich allein in Heimkabine 1 oder 2.
- Alle Jugendteams gleichen Geschlechts dürfen Kabinen teilen; Heim und Gast bleiben getrennt.
- Freie Auswärtskabinen auch für Heimteams; unklare Zuordnungen werden angezeigt.
- Gesuche erstellen, abschließen und wieder öffnen, nur mit Admin-Anmeldung.

## Entwicklung und Tests

```sh
npm ci
npm run typecheck
npm test
npm run build
npm run package
```

`npm test` benötigt PHP (alternativ `PHP_BIN=/pfad/php npm test`). Die Referenztests prüfen PHP gegen die bisherige TypeScript-Implementierung. Zusätzlicher HTTP-/MySQL-Integrationstest: `node tests/http.mjs http://127.0.0.1:8080` mit ausschließlich lokaler Testinstallation und den in der Datei genannten Test-Umgebungsvariablen.

Alle Zugangsdaten bleiben in der ignorierten `config.php`. Die Helferbörse startet leer; bestehende Produktionsdaten müssen separat übertragen werden. Die Laufzeit verwendet als externe Datenquellen weiterhin FUSSBALL.DE und die Vereinswebsite (Logos).

Die bisherige Sites-App v0.9 liegt weiterhin auf `vereinsapp-v0.8`. Auf `main` liegt die separat entwickelte PHP-Anwendung. Dieser Branch veröffentlicht keine native APK/iOS-App.
