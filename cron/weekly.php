<?php
// Woechentlicher Automatismus (Abschnitt 12), z.B. sonntags per STRATO-Cron:
//   0 18 * * 0 /usr/bin/php /pfad/zur/anwendung/cron/weekly.php

require __DIR__ . '/bootstrap.php';

use App\Services\NotificationService;

cron_log('Starte woechentlichen Lauf.');

NotificationService::sendWeeklyAdminDigest();
cron_log('Woechentliche Konfliktuebersicht an Admins erzeugt.');

$sent = NotificationService::flushPending();
cron_log("{$sent} Benachrichtigungen versendet.");

cron_log('Woechentlicher Lauf abgeschlossen.');
