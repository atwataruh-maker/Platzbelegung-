# Platzbelegung VfL Ramsdorf

Webanwendung zur automatisierten Verwaltung der Trainings- und Spielbelegung
der Sportplaetze und Hallen des VfL Ramsdorf. Grundregel: **Spiele haben
Vorrang vor Training** - angesetzte Heimspiele sperren automatisch den
betroffenen Platz (inkl. Teilflaechen), verdraengte Trainings bekommen
automatisch einen Ausweichplatz vorgeschlagen.

Dies ist die MVP-Version ("Version 1" der Anforderungsliste):

- Login fuer Admin und Trainer (Rollen: `admin`, `trainer`, `betreuer`)
- Stammdaten: Mannschaften, Plaetze/Teilflaechen, Saisons
- Sommer-/Winter-/Hallenplan als Grundbelegung hinterlegbar
- Wochen- und Tagesansicht (Plaetze als Spalten, 30-Minuten-Raster)
- Spiele manuell anlegen
- automatische Platzblockierung (Vorlauf/Spieldauer/Nachlauf, inkl. Teilflaechen)
- automatische Konflikterkennung (Training vs. Sperre, Doppelbelegung, keine Alternative)
- automatische Ausweichvorschlaege mit Trainerbestaetigung
- E-Mail-Benachrichtigungen (per PHP `mail()`, kein externer Dienst noetig)
- oeffentliche, anonymisierte Wochenansicht ohne Login
- Aenderungsprotokoll

## Technischer Ansatz

Bewusst **ohne** Composer/Node/Docker gebaut, damit die Anwendung per
einfachem FTP-Upload auf ein STRATO-PHP-/MySQL-Webhosting passt:

- PHP 8.2+ (getestet mit 8.4), eigener schlanker Autoloader (`src/autoload.php`)
- MySQL/MariaDB via PDO
- Kein Frontend-Build: reines PHP-Templating + eine handgeschriebene CSS-Datei
- E-Mail-Versand ueber die eingebaute `mail()`-Funktion (kein PHPMailer/SMTP-Paket noetig)

## Verzeichnisstruktur

```
public/            Document Root (Front-Controller, .htaccess, CSS)
src/Core/           Router, DB, Auth, View, Csrf, Mailer, ...
src/Models/         Datenzugriff (PDO)
src/Services/       Fachlogik: Platzsperren, Konflikte, Ausweichvorschlaege, Benachrichtigungen
src/Controllers/    HTTP-Controller
src/views/          PHP-Templates
database/           schema.sql, seed.sql, Passwort-Hash-Helfer
cron/               daily.php, weekly.php (fuer STRATO-Cron-Jobs)
config/             config.example.php (kopieren nach config.php)
```

## Einrichtung (lokal / Entwicklung)

1. MySQL/MariaDB-Datenbank anlegen und `database/schema.sql` einspielen,
   optional `database/seed.sql` fuer Beispieldaten:
   ```
   mysql -u root -e "CREATE DATABASE platzbelegung CHARACTER SET utf8mb4;"
   mysql -u root platzbelegung < database/schema.sql
   mysql -u root platzbelegung < database/seed.sql
   ```
2. `config/config.example.php` nach `config/config.php` kopieren und die
   Datenbank-Zugangsdaten eintragen.
3. Eigenes Passwort setzen statt des Demo-Passworts aus `seed.sql`:
   ```
   php database/generate_password_hash.php "MeinNeuesPasswort"
   ```
   den erzeugten Hash in der Tabelle `users` (Spalte `password_hash`) fuer den
   jeweiligen Account eintragen.
4. Lokal testen mit dem eingebauten PHP-Webserver:
   ```
   php -S 127.0.0.1:8080 -t public
   ```
   Demo-Login (falls `seed.sql` eingespielt wurde): `admin@vfl-ramsdorf.de` /
   `trainer@vfl-ramsdorf.de`, Passwort jeweils `wechsel-mich` (bitte sofort
   aendern).

## Deployment auf STRATO-Webhosting

1. Kompletten Projektordner per FTP hochladen. **Wichtig:** Nur der Inhalt
   von `public/` darf als Document Root / oeffentlich erreichbar sein
   (bei STRATO im Kundenmenue als Verzeichnis fuer die Domain einstellen,
   bzw. `public/` als Basisverzeichnis waehlen). `src/`, `config/`,
   `database/`, `cron/` und `storage/` sollten ausserhalb des Web-Roots
   liegen bzw. per `.htaccess`/Serverkonfiguration nicht erreichbar sein.
2. MySQL-Datenbank im STRATO-Kundenmenue anlegen, Zugangsdaten notieren.
3. `database/schema.sql` (und ggf. `database/seed.sql`) ueber
   phpMyAdmin/den STRATO-DB-Zugang importieren.
4. `config/config.php` auf dem Server anlegen (Kopie von
   `config.example.php`) und mit den echten Zugangsdaten fuellen. Diese
   Datei niemals ins Repository committen.
5. Rechte pruefen: `storage/logs/` muss beschreibbar sein.
6. Zwei Cron-Jobs im STRATO-Kundenmenue einrichten:
   - taeglich, z.B. `php /pfad/zur/anwendung/cron/daily.php`
   - woechentlich (z.B. sonntags), `php /pfad/zur/anwendung/cron/weekly.php`

   STRATO-Cron-Jobs laufen "best effort" und koennen sich verzoegern - die
   Skripte sind bewusst idempotent, ein verspaeteter oder doppelter Lauf
   verursacht keine Doppel-Buchungen oder doppelten Konflikte.
7. Fuer den Versand von E-Mails ueber `mail()` muss die Absenderadresse in
   `config.php` (`mail.from_address`) zur gehosteten Domain passen (SPF).

## Wichtige Konzepte

- **Teilflaechen:** In `pitches`/`pitch_relations` koennen Plaetze als
  "ganzer Platz" (z.B. Rasen Hauptplatz) mit mehreren Teilflaechen (Rasen
  1/3, 2/3, 3/3) modelliert werden. Eine Sperre des ganzen Platzes sperrt
  automatisch alle Teilflaechen (siehe `PitchRelation::relatedPitchIds()`).
- **Automatische Platzsperre:** `BlockingService` berechnet aus Anstoss,
  Vorlauf-, Spieldauer- und Nachlaufzeit die Sperrzeit und legt fuer alle
  betroffenen Plaetze `pitch_blocks`-Eintraege an.
- **Konflikterkennung:** `ConflictService::scanRange()` prueft die
  Grundbelegung gegen Platzsperren und gegen sich selbst (Doppelbelegung)
  und loest automatisch Konflikte auf, deren Ursache nicht mehr besteht.
- **Ausweichvorschlaege:** `FallbackService` sucht einen freien, passenden
  Ausweichplatz zur selben Uhrzeit und legt Trainerbestaetigungen +
  Benachrichtigungen an.

## Nicht enthalten (geplant fuer Version 2/3, siehe Anforderungsliste)

CSV-Import aus DFBnet, Fussball.de-Widget-Auswertung, PWA/WhatsApp-Vorlagen,
Ferien-/Turniermodus-Automatik, automatische Zeitverschiebung (aktuell wird
nur der Platz getauscht, nicht die Uhrzeit).
