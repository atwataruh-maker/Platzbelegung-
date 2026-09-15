<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Conflict;
use App\Models\ScheduleOverride;
use App\Services\ScheduleGridService;
use DateTimeImmutable;

class DayViewController
{
    public function index(?string $date = null): void
    {
        Auth::requireLogin();

        $date = $date ?: (new DateTimeImmutable('today'))->format('Y-m-d');
        $grid = ScheduleGridService::buildDayGrid($date);

        $conflicts = Conflict::openForRange("{$date} 00:00:00", "{$date} 23:59:59");
        $moves = ScheduleOverride::forDateWithDetails($date);

        $prev = (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
        $next = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');

        View::render('day/index', [
            'grid' => $grid,
            'conflicts' => $conflicts,
            'moves' => $moves,
            'prevDate' => $prev,
            'nextDate' => $next,
        ]);
    }
}
