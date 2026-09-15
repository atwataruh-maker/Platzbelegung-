<?php
// Taeglicher Automatismus (Abschnitt 12). Per STRATO-Cron aufrufen, z.B.:
//   0 5 * * * /usr/bin/php /pfad/zur/anwendung/cron/daily.php
// STRATO-Cron-Jobs laufen "best effort" und koennen sich verzoegern - dieses
// Skript ist bewusst idempotent, ein mehrfacher/verspaeteter Lauf schadet nicht.

require __DIR__ . '/bootstrap.php';

use App\Core\Config;
use App\Models\Game;
use App\Services\BlockingService;
use App\Services\ConflictService;
use App\Services\NotificationService;

$horizonDays = (int) Config::get('rules.planning_horizon_days', 21);
$today = new DateTimeImmutable('today');
$start = $today->format('Y-m-d');
$end = $today->modify("+{$horizonDays} days")->format('Y-m-d');

cron_log("Starte taeglichen Lauf fuer Zeitraum {$start} bis {$end}");

$games = Game::upcomingHomeGames($horizonDays);
foreach ($games as $game) {
    BlockingService::syncForGame($game);
}
cron_log(count($games) . ' anstehende Heimspiele geprueft, Platzsperren synchronisiert.');

ConflictService::scanRange($start, $end);
cron_log('Konflikterkennung fuer Planungshorizont durchgefuehrt.');

$sent = NotificationService::flushPending();
cron_log("{$sent} Benachrichtigungen versendet.");

cron_log('Taeglicher Lauf abgeschlossen.');
