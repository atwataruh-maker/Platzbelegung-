# VfL Ramsdorf · STRATO-Installation (v0.10.1)

Die App läuft auf PHP und MySQL/MariaDB. Cloudflare, ChatGPT-Anmeldung und Node.js auf dem Webserver sind nicht erforderlich. Für Spiele und Logos werden weiterhin FUSSBALL.DE und die Vereinswebsite abgerufen. Das öffentliche GitHub-Repository enthält nur Quellcode und ein leeres Installationspaket, keine Passwörter oder bestehenden Helfergesuche.

## 1. Voraussetzungen prüfen

- STRATO-Webhosting mit PHP **8.2 oder neuer** (geprüft mit PHP 8.3).
- PHP-Erweiterungen **curl, mbstring, PDO und pdo_mysql**, JSON und Sessions.
- MySQL 8 oder MariaDB 10.6+; eine eigene leere Datenbank wird empfohlen.
- Apache mit `mod_rewrite`, `.htaccess`, `Require all denied` und Options-Regeln. Das tatsächliche Paket muss der Webseiten-Admin prüfen.
- HTTPS-Zertifikat und ausgehende HTTPS-Verbindungen zu `www.fussball.de`.
- Schreibbarer Speicher für Cache und Sessions. Wenn möglich **außerhalb des öffentlich erreichbaren Webverzeichnisses**.

## 2. Fertige Dateien hochladen

1. Im GitHub-Branch **strato-php** auf **Code → Download ZIP** klicken und entpacken.
2. **Nur den Inhalt von `upload/`**, einschließlich der versteckten Datei **`.htaccess`**, per SFTP/FTPS in ein neues Verzeichnis hochladen. Die vorhandene Vereinswebsite nicht überschreiben.
3. Eine Subdomain, zum Beispiel `app.vfl-ramsdorf.de`, auf dieses Verzeichnis zeigen lassen. Alternativ ist ein Unterverzeichnis wie `/vereinsapp` möglich.
4. `config.example.php` auf dem Server in **`config.php`** kopieren und bearbeiten:
   - `app_url`: vollständige öffentliche HTTPS-Adresse, ggf. einschließlich `/vereinsapp`, ohne abschließenden Schrägstrich.
   - `database`: Host, Datenbankname, Benutzer und Passwort aus dem STRATO-Kundenbereich. Der Host ist nicht zwingend `localhost`.
   - `storage_path`: absoluter Pfad zum beschreibbaren Speicherverzeichnis. Standard ist `var/` im App-Verzeichnis, durch `.htaccess` gesperrt.
   - `setup_token`: einen zufälligen, einzigartigen Schlüssel mit mindestens **32 Zeichen** aus einem Passwortgenerator eintragen. Kein Beispielpasswort übernehmen.
   - `legal_url` und `privacy_url`: Links zum passenden Impressum und zur Datenschutzerklärung dieser App eintragen.
5. `config.php` niemals auf GitHub hochladen. Dateirechte so restriktiv setzen, wie der PHP-Prozess des Hosting-Pakets es erlaubt; keine pauschalen 777-Rechte.

## 3. Einmalige Einrichtung

1. `https://DEINE-APP-ADRESSE/install.php` im Browser öffnen.
2. Setup-Schlüssel aus `config.php` eingeben und ein Admin-Konto mit einem eigenen Passwort anlegen (12–72 Bytes; am besten ein langes, zufälliges ASCII-Passwort).
3. Der Installer legt Tabellen mit Präfix `vfl_` an. Er fasst bestehende Vereinstabellen nicht an. Bei bereits vorhandenem Admin sperrt er sich.
4. Nach Erfolg **`install.php` vom Server löschen** und **`setup_token` in `config.php` leeren**.
5. Unter `/admin` anmelden. Unter „Ehrenamt & Helfen“ erscheint dann **Helfer suchen**; Gesuche lassen sich abschließen und wieder öffnen.

Die Pläne und veröffentlichten Gesuche sind für Besucher ohne Anmeldung sichtbar. Nur angemeldete Admins können Gesuche verändern. Passwörter werden gehasht; Schreibzugriffe erfordern Session, passenden Origin und CSRF-Token. Anmeldeversuche werden begrenzt. Nach zwei Stunden Inaktivität endet die Admin-Sitzung. Bei Nutzung der Helferbörse nur Kontaktdaten veröffentlichen, deren Veröffentlichung abgestimmt ist.

## 4. Abnahme vor Freigabe

- Startseite, Platzbelegung, Kabinenbelegung, Helferbörse und `/admin` auf iPhone und Android öffnen.
- Datum zurück/vor, 7-Tage-Spiele, Ergebnisanzeige und Original-PDF prüfen.
- Bei Spielabrufausfall muss ein Warnhinweis erscheinen; automatische Platzverschiebungen dürfen dann nicht als bestätigt gelten.
- Als Gast sind keine Schreibaktionen möglich. Als Admin ein Testgesuch erstellen, abschließen und wieder öffnen.
- `config.php`, `server/training.json`, `server/schema.sql`, `var/` und `frontend-dist/index.html` dürfen von außen **nicht abrufbar** sein (403). Bei einer 200-Antwort mit Dateiinhalt nicht freigeben; `.htaccess` zuerst korrigieren.
- Installationsseite muss gelöscht sein. HTTPS, Impressum und Datenschutzerklärung prüfen.
- Verknüpfungen auch dann prüfen, wenn die App in einem Unterverzeichnis liegt.

### Umzug bestehender Helfergesuche

Das Paket startet mit einer **leeren Helferbörse**. Ein automatischer Export aus der bisherigen Cloudflare-Datenbank ist nicht enthalten. Wenn vorhandene echte Gesuche übernommen werden sollen, diese vor dem Wechsel exportieren und kontrolliert in `vfl_helper_requests` importieren. Keine Produktionsdaten im öffentlichen Repository ablegen.

## 5. Laufender Betrieb

- Bei unlesbaren Ergebnissen erfolgt alle 30 Sekunden ein neuer Versuch. Bereits bekannte Spielstände bleiben höchstens 24 Stunden sichtbar und werden als „Letzter Stand“ markiert.
- Spiele werden beim Seitenaufruf abgerufen, serverseitig 5 Minuten zwischengespeichert und bei geöffneter App alle 5 Minuten aktualisiert. Ein Cronjob ist **nicht erforderlich**.
- Bei Quellenfehlern werden vorhandene Spieldaten höchstens 24 Stunden als **veraltet** angezeigt. Ohne nutzbare Spieldaten pausieren automatische Platzverschiebungen.
- Trainingsplan: `server/training.json`, Original-PDF: `belegungsplan-sommer-2026.pdf`. Beides gemeinsam bei Saisonwechsel aktualisieren; die JSON-Zeiten sind Minuten nach Mitternacht.
- SF Nordvelen bleibt bei den Kabinen ungeklärt, solange die Geschlechtszuordnung fehlt. Keine automatische Annahme.
- Regelmäßig Datenbank und `config.php` sicher außerhalb des Webspace sichern; PHP aktuell halten.
- Neue Version: Datenbank sichern, Upload-Dateien austauschen, **config.php und var/ erhalten**, Installer nicht erneut hochladen. Datenbankschema-Änderungen immer anhand der Versionshinweise prüfen.
- Bei Problemen Serverfehlerprotokoll lesen. Besucher erhalten keine Zugangsdaten oder Datenbankfehler.
- Admin-Passwort vergessen: lokal mit PHP `password_hash` einen neuen Hash erzeugen und als Datenbankadministrator nur den Hash in `vfl_admins.password_hash` ändern. Danach Session-Dateien löschen, um alte Anmeldungen zu beenden.

## Entwicklung (nicht auf dem Hosting nötig)

Node.js >=22.13 und npm; PHP 8.2+ für Tests:

```sh
npm ci
npm run typecheck
npm test
npm run build
npm run package
```

`upload/` ist das fertig kompilierte Upload-Paket und wird mitversioniert. Nach Änderungen an PHP oder Oberfläche das Paket neu erzeugen. Für einen lokalen HTTP-Test kann `php -S 127.0.0.1:8080 tools/router.php` dienen; `config.php` muss dafür `app_url` auf `http://127.0.0.1:8080` setzen und eine lokale Testdatenbank verwenden. Das ist keine Produktionskonfiguration.

Die Referenztests vergleichen die PHP-Zuordnung mit den bewährten TypeScript-Regeln. Die Belegungsregeln laufen auf dem PHP-Server; React läuft ausschließlich als gebündeltes JavaScript im Browser. Die bisherige Sites-Version bleibt separat im Branch vereinsapp-v0.8 erhalten.
