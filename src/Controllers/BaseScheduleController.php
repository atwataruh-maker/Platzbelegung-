<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Url;
use App\Core\View;
use App\Models\BaseSchedule;
use App\Models\ChangeLog;
use App\Models\Pitch;
use App\Models\Season;
use App\Models\Team;

class BaseScheduleController
{
    private const WEEKDAYS = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];

    public function index(): void
    {
        Auth::requireRole('admin');
        View::render('base_schedule/index', [
            'entries' => BaseSchedule::allWithNames(),
            'teams' => Team::allWithTrainers(),
            'pitches' => Pitch::allActive(),
            'seasons' => Season::all('start_date DESC'),
            'weekdays' => self::WEEKDAYS,
        ]);
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $data = $this->extractData();
        $id = BaseSchedule::create($data);
        ChangeLog::record(Auth::id(), 'base_schedule', $id, 'created', 'Grundbelegung angelegt');

        Flash::set('success', 'Trainingszeit angelegt.');
        header('Location: ' . Url::to('/admin/base-schedule'));
        exit;
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $data = $this->extractData();
        BaseSchedule::update((int) $id, $data);
        ChangeLog::record(Auth::id(), 'base_schedule', (int) $id, 'updated', 'Grundbelegung geaendert');

        Flash::set('success', 'Trainingszeit aktualisiert.');
        header('Location: ' . Url::to('/admin/base-schedule'));
        exit;
    }

    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        BaseSchedule::update((int) $id, ['active' => 0]);
        ChangeLog::record(Auth::id(), 'base_schedule', (int) $id, 'deactivated', 'Grundbelegung deaktiviert');

        Flash::set('success', 'Trainingszeit entfernt.');
        header('Location: ' . Url::to('/admin/base-schedule'));
        exit;
    }

    private function extractData(): array
    {
        return [
            'team_id' => (int) Request::input('team_id'),
            'pitch_id' => (int) Request::input('pitch_id'),
            'season_id' => (int) Request::input('season_id'),
            'weekday' => (int) Request::input('weekday'),
            'start_time' => Request::input('start_time'),
            'end_time' => Request::input('end_time'),
            'note' => trim((string) Request::input('note', '')) ?: null,
        ];
    }
}
