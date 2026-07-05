<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Conflict;
use App\Services\ScheduleGridService;
use DateTimeImmutable;

class WeekViewController
{
    public function index(string $offset = '0'): void
    {
        Auth::requireLogin();

        $offset = (int) $offset;
        $monday = (new DateTimeImmutable('monday this week'))->modify(($offset * 7) . ' days');

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $monday->modify("+{$i} days")->format('Y-m-d');
            $days[] = ScheduleGridService::buildDayGrid($date);
        }

        View::render('week/index', [
            'days' => $days,
            'offset' => $offset,
            'weekLabel' => $monday->format('d.m.Y') . ' - ' . $monday->modify('+6 days')->format('d.m.Y'),
            'openConflicts' => Conflict::allOpen(),
        ]);
    }
}
