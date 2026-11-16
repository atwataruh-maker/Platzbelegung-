# VfL Ramsdorf · Update auf v0.14.1

Die ZIP enthält alle geänderten Dateien seit v0.13.0 und lässt sich auch auf ältere Stände ab v0.11.x einspielen.

## Änderungen
- **Kein Training am eigenen Spieltag:** Hat eine Vereinsmannschaft ein Heim-, Auswärtsspiel oder Vereinsduell, entfällt ihr Training an diesem Tag. Es belegt keinen Platz und keine Kabine mehr. Die Platzbelegung zeigt oben „Training entfällt · Spieltag“ mit Grund und Anstoßzeit.
- Abgesagte oder verlegte Spiele heben kein Training auf. Zusammengelegte Gruppen wie E1/E2-Juniorinnen trainieren weiter.
- **Kabinen-Kacheln** zeigen, mit wem die Kabine geteilt wird, z. B. „Geteilt mit D2-Junioren (17:30–19:15)“.
- **Spielkarten** nennen die Vereinsmannschaft, z. B. „D1-Junioren · D-Junioren“ über „VfL Ramsdorf 1 vs. …“.

## Installation auf STRATO
1. Bestehendes `/app/` sichern.
2. `vfl-update-0.14.1.zip` herunterladen und entpacken.
3. Inhalt in `/app/` übertragen, Ordner zusammenführen: zuerst `server/`, dann `index.php`, dann `assets/`, zuletzt `frontend-dist/index.html`.
4. **config.php, var/, server/training.json und die Datenbank unverändert lassen.** Kein Installer nötig.
5. Browsercache aktualisieren, oben v0.14.1 prüfen. An einem Spieltag in der Platzbelegung den Hinweis „Training entfällt“ und die Mannschaft auf der Spielkarte prüfen.
