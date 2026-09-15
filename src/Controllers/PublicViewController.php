<?php

namespace App\Controllers;

use App\Core\View;
use App\Models\Game;
use App\Services\ScheduleGridService;
use DateTimeImmutable;

class PublicViewController
{
    /** Oeffentliche Wochenansicht ohne Login, ohne private Trainerdaten/Konflikte (Abschnitt 11). */
    public function week(string $offset = '0'): void
    {
        $offset = (int) $offset;
        $monday = (new DateTimeImmutable('monday this week'))->modify(($offset * 7) . ' days');

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $monday->modify("+{$i} days")->format('Y-m-d');
            $days[] = ScheduleGridService::buildDayGrid($date);
        }

        View::render('public/week', [
            'days' => $days,
            'offset' => $offset,
            'weekLabel' => $monday->format('d.m.Y') . ' - ' . $monday->modify('+6 days')->format('d.m.Y'),
        ], 'public/layout');
    }

    /** Oeffentliche Tagesansicht ohne Login, ohne private Trainerdaten/Konflikte. */
    public function day(?string $date = null): void
    {
        $date = $date ?: (new DateTimeImmutable('today'))->format('Y-m-d');
        $grid = ScheduleGridService::buildDayGrid($date);

        $monday = (new DateTimeImmutable($date))->modify('monday this week');
        $homeGames = Game::homeGamesForWeek($monday->format('Y-m-d'));

        $prev = (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
        $next = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');

        View::render('public/day', [
            'grid' => $grid,
            'prevDate' => $prev,
            'nextDate' => $next,
            'homeGames' => $homeGames,
            'weekLabel' => $monday->format('d.m.Y') . ' - ' . $monday->modify('+6 days')->format('d.m.Y'),
        ], 'public/layout');
    }
}
