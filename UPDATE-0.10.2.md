# Update 0.10 / 0.10.1 → 0.10.2

Die neue Mittwochsregel gilt ab 28.10.2026 wiederkehrend für das Training:

| Mannschaft | Kabine | Reservierte Zeit laut aktuellem Trainingsplan |
| --- | --- | --- |
| C-Juniorinnen und D1-Juniorinnen | 1. Heimkabine, gemeinsam | 17:15–19:15 |
| D1-Junioren | 3. Heimkabine | 16:15–18:15 |
| E2-Junioren | 3. Heimkabine | 17:45–19:45 |
| Alte Herren | 1. Heimkabine im Anschluss | 19:15–21:15 |

Die Reservierungen enthalten jeweils 15 Minuten vor und nach dem Training. Deshalb überlappen D1 und E2 im Plan während des Wechsels. Die Trainingszeiten selbst werden nicht geändert.

Die festen Kabinen der 1. und 2. Herren, alleinige Nutzung durch Damen und Geschlechtertrennung gelten weiterhin. Die Mittwochsregel ist die bevorzugte Verteilung innerhalb dieser Vorgaben. Verhindern andere Belegungen die Zuordnung, erscheint ein Hinweis über dem Kabinenplan; nicht zuweisbare Teams werden gesondert aufgeführt. Andere Wochentage und vergangene Daten vor dem 28.10. sind nicht betroffen.

## Aufspielen durch den Webseiten-Admin

1. App-Dateien und Datenbank sichern.
2. `vfl-update-0.10.2.zip` herunterladen und entpacken.
3. Inhalt der Ordner `server`, `assets` und `frontend-dist` in das bestehende Verzeichnis `/app/` hochladen. Neue Assets zuerst, `frontend-dist/index.html` zuletzt übertragen; gleichnamige Dateien ersetzen.
4. `config.php`, `var/`, Trainingsdatei und Datenbank erhalten. Installer nicht erneut ausführen. Die ZIP enthält nur die notwendigen Update-Dateien und diese Anleitung.
5. Seite neu laden: Version 0.10.2 muss erscheinen. In der Kabinenbelegung den 28.10. und 04.11. prüfen.

Das Paket enthält auch die Ergebnis- und Anmeldeverbesserungen aus 0.10.1 und kann auf 0.10 oder 0.10.1 angewendet werden. Installation auf dem produktiven Webspace ist durch den Admin erforderlich.
