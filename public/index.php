<?php

require dirname(__DIR__) . '/src/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\BaseScheduleController;
use App\Controllers\ChangeLogController;
use App\Controllers\ConflictController;
use App\Controllers\DashboardController;
use App\Controllers\DayViewController;
use App\Controllers\GameController;
use App\Controllers\ImportController;
use App\Controllers\MyTeamController;
use App\Controllers\PitchController;
use App\Controllers\PublicViewController;
use App\Controllers\SeasonController;
use App\Controllers\TeamController;
use App\Controllers\WeekViewController;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Request;
use App\Core\Router;

$config = Config::load();
date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Berlin');

Auth::start();

$router = new Router();

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/', [DashboardController::class, 'index']);

$router->get('/week', [WeekViewController::class, 'index']);
$router->get('/week/{offset}', [WeekViewController::class, 'index']);
$router->get('/day', [DayViewController::class, 'index']);
$router->get('/day/{date}', [DayViewController::class, 'index']);
$router->get('/my-team', [MyTeamController::class, 'index']);
$router->post('/my-team/confirm/{id}', [MyTeamController::class, 'confirm']);
$router->post('/my-team/decline/{id}', [MyTeamController::class, 'decline']);

$router->get('/public/week', [PublicViewController::class, 'week']);
$router->get('/public/week/{offset}', [PublicViewController::class, 'week']);

// Maschinelle Import-Schnittstelle (kein Session-Login, Token-Absicherung siehe ImportController)
$router->post('/import/games', [ImportController::class, 'games']);

$router->get('/admin/teams', [TeamController::class, 'index']);
$router->post('/admin/teams', [TeamController::class, 'store']);
$router->post('/admin/teams/{id}/update', [TeamController::class, 'update']);
$router->post('/admin/teams/{id}/delete', [TeamController::class, 'delete']);

$router->get('/admin/pitches', [PitchController::class, 'index']);
$router->post('/admin/pitches', [PitchController::class, 'store']);
$router->post('/admin/pitches/{id}/update', [PitchController::class, 'update']);
$router->post('/admin/pitches/{id}/delete', [PitchController::class, 'delete']);
$router->post('/admin/pitches/{id}/relations/link', [PitchController::class, 'linkRelation']);
$router->post('/admin/pitches/{id}/relations/unlink', [PitchController::class, 'unlinkRelation']);

$router->get('/admin/seasons', [SeasonController::class, 'index']);
$router->post('/admin/seasons', [SeasonController::class, 'store']);
$router->post('/admin/seasons/{id}/activate', [SeasonController::class, 'activate']);

$router->get('/admin/base-schedule', [BaseScheduleController::class, 'index']);
$router->post('/admin/base-schedule', [BaseScheduleController::class, 'store']);
$router->post('/admin/base-schedule/{id}/update', [BaseScheduleController::class, 'update']);
$router->post('/admin/base-schedule/{id}/delete', [BaseScheduleController::class, 'delete']);

$router->get('/admin/games', [GameController::class, 'index']);
$router->post('/admin/games', [GameController::class, 'store']);
$router->post('/admin/games/{id}/update', [GameController::class, 'update']);
$router->post('/admin/games/{id}/cancel', [GameController::class, 'cancel']);
$router->post('/admin/games/{id}/delete', [GameController::class, 'delete']);

$router->get('/admin/conflicts', [ConflictController::class, 'index']);
$router->post('/admin/conflicts/{id}/resolve', [ConflictController::class, 'resolve']);
$router->post('/admin/conflicts/{id}/ignore', [ConflictController::class, 'ignore']);
$router->post('/admin/conflicts/rescan', [ConflictController::class, 'rescan']);

$router->get('/admin/log', [ChangeLogController::class, 'index']);

$router->dispatch(Request::method(), Request::path());
