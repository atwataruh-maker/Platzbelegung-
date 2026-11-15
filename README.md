# VfL Ramsdorf Vereins-App · v0.13.1 · PHP/STRATO

**Branch `strato-php`: eigenständig auf PHP-Webhosting betreibbare Vereins-App.**

Platzbelegung, Kabinenbelegung, Spiele & Ergebnisse, Breitensport, Helferbörse und eigener Admin-Login. Neue Startseite mit Tageshinweisen, Ergebnissen der letzten 48 Stunden und aktuellem Bomber. PHP verarbeitet Spiele und berechnet die Platz- und Kabinenbelegung. Gesuche werden in MySQL/MariaDB gespeichert.

**Bestehende Installation aktualisieren:** [Update 0.13.1](UPDATE-0.13.1.md) · [Fertiges Update-ZIP](vfl-update-0.13.1.zip). Von v0.11.x zuerst [Update 0.13.0](UPDATE-0.13.0.md).

## Direkt installieren

**[Installationsanleitung](INSTALLATION.md)** lesen. Das Verzeichnis **[upload/](upload/)** enthält das fertige Paket einschließlich JavaScript/CSS. Nur dessen Inhalt in ein neues Webverzeichnis hochladen, config.php einrichten und den einmaligen Installer ausführen. Node.js, Composer, Cloudflare und ChatGPT sind auf dem Zielserver nicht nötig.

Voraussetzung: PHP >=8.2 mit curl, mbstring, DOM/XML, PDO/pdo_mysql, MySQL 8 oder MariaDB >=10.6, HTTPS und Apache/.htaccess. Der konkrete STRATO-Tarif und die Serverkonfiguration müssen beim Betreiber geprüft werden. Kein Live-Upload auf STRATO wurde durchgeführt.

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

Alle Zugangsdaten bleiben in der ignorierten `config.php`. Die Helferbörse startet leer; bestehende Produktionsdaten müssen separat übertragen werden. Die Laufzeit verwendet als externe Datenquellen weiterhin FUSSBALL.DE und die Vereinswebsite (Logos, Bomber, Breitensport-Kurse).

Die bisherige Sites-App v0.9 liegt weiterhin auf `vereinsapp-v0.8`. Auf `main` liegt die separat entwickelte PHP-Anwendung. Dieser Branch veröffentlicht keine native APK/iOS-App.

## Tagesbanner auf der Hauptseite

Drei Bildbanner zeigen heutige Spiele, Platz- und Kabinenverschiebungen. Automatischer Wechsel alle fünf Sekunden, Wischen und Pause. Bei unvollständigem Spielabgleich wird keine reguläre Belegung bestätigt. Updates: siehe `UPDATE-0.10.7.md`.

## Breitensport (Reiter „Sport“)

Wochenplan Mo–Sa mit Filter nach Zielgruppe, Kursübersicht mit freien Plätzen und Buchungslink sowie eine Seite je Angebot mit Zeiten, Übungsleitung, Ort und Kalenderexport. Die Startseite bleibt unverändert.

- Feste Angebote, Zeiten, Übungsleitungen und Ansprechpartner stehen in `server/breitensport.json` und können dort ohne neuen Build angepasst werden. Bei Abweichungen auf der Vereinswebsite gelten deren Detail- und Kursseiten.
- Kurse (Zeitraum, Preise, freie Plätze, Buchungslink) werden von `www.vfl-ramsdorf.de/breitensport/kurse` gelesen und 30 Minuten zwischengespeichert. Ist die Website nicht erreichbar, wird bis zu sieben Tage der letzte Stand angezeigt; ohne Kursdaten bleibt der gepflegte Wochenplan sichtbar.
- Angebote mit festen Einzelterminen (`dates`, z. B. Sportabzeichen) erscheinen nur an diesen Tagen; einmalige Veranstaltungen (`events`) stehen unter „Nächste Termine“.
- Kurstermine erscheinen im Wochenplan nur, solange der Kurs auf der Website ausgeschrieben ist und das Datum im Kurszeitraum liegt.
