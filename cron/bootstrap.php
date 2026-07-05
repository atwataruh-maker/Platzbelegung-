<?php
// Gemeinsamer Bootstrap fuer Cron-Skripte. Nur per CLI aufrufbar, damit die
// Skripte nicht ueber die oeffentliche Web-URL ausgeloest werden koennen.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('Nur per CLI/Cron ausfuehrbar.');
}

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Config;

$config = Config::load();
date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Berlin');

function cron_log(string $message): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    echo $line;
    $logFile = dirname(__DIR__) . '/storage/logs/cron.log';
    @file_put_contents($logFile, $line, FILE_APPEND);
}
