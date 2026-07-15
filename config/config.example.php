<?php
// Kopieren nach config/config.php und mit echten Zugangsdaten fuellen.
// config/config.php ist per .gitignore ausgeschlossen und sollte niemals
// mit echten Zugangsdaten eingecheckt werden.

return [
    'db' => [
        'host' => 'localhost',
        'name' => 'dbname',
        'user' => 'dbuser',
        'pass' => 'dbpass',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        // Basis-URL ohne abschliessenden Slash, z.B. https://platzbelegung.vfl-ramsdorf.de
        'base_url' => 'http://localhost',
        'timezone' => 'Europe/Berlin',
        'session_name' => 'vfl_pb_session',
        // wird als Absender-Adresse fuer automatisch generierte Cron-Aufrufe genutzt
        'cron_token' => 'bitte-aendern-zufaelliger-string',
    ],
    'mail' => [
        'from_address' => 'noreply@vfl-ramsdorf.de',
        'from_name' => 'VfL Ramsdorf Platzbelegung',
        // true = Mails wirklich versenden (PHP mail()), false = nur in notifications-Tabelle/Log schreiben
        'enabled' => true,
    ],
    'import' => [
        // Geheimtoken fuer den maschinellen Spielplan-Import (POST /import/games).
        // Muss identisch mit dem GitHub-Actions-Secret IMPORT_API_TOKEN sein.
        // Mit z.B. `php -r "echo bin2hex(random_bytes(32));"` erzeugen.
        'api_token' => 'bitte-zufaelligen-token-erzeugen',
    ],
    'rules' => [
        // Standard-Spieldauer/Vor-/Nachlaufzeit, falls beim Spiel nichts anderes hinterlegt ist
        'default_duration_minutes' => 90,
        'default_lead_minutes' => 60,
        'default_lag_minutes' => 30,
        // wie viele Tage im Voraus der Cron-Job Spiele/Konflikte betrachtet
        'planning_horizon_days' => 21,
    ],
];
