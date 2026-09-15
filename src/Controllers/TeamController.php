<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Url;
use App\Core\View;
use App\Models\ChangeLog;
use App\Models\Pitch;
use App\Models\Team;
use App\Models\User;

class TeamController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        View::render('teams/index', [
            'teams' => Team::allWithTrainers(),
            'trainers' => User::trainers(),
            'pitches' => Pitch::allActive(),
        ]);
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $data = $this->extractData();
        $id = Team::create($data);
        ChangeLog::record(Auth::id(), 'team', $id, 'created', "Mannschaft '{$data['name']}' angelegt");

        Flash::set('success', 'Mannschaft angelegt.');
        header('Location: ' . Url::to('/admin/teams'));
        exit;
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $data = $this->extractData();
        Team::update((int) $id, $data);
        ChangeLog::record(Auth::id(), 'team', (int) $id, 'updated', "Mannschaft '{$data['name']}' geaendert");

        Flash::set('success', 'Mannschaft aktualisiert.');
        header('Location: ' . Url::to('/admin/teams'));
        exit;
    }

    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        Team::update((int) $id, ['active' => 0]);
        ChangeLog::record(Auth::id(), 'team', (int) $id, 'deactivated', 'Mannschaft deaktiviert');

        Flash::set('success', 'Mannschaft deaktiviert.');
        header('Location: ' . Url::to('/admin/teams'));
        exit;
    }

    private function extractData(): array
    {
        return [
            'name' => trim((string) Request::input('name')),
            'age_group' => trim((string) Request::input('age_group', '')) ?: null,
            'gender' => Request::input('gender', 'gemischt'),
            'trainer_user_id' => Request::input('trainer_user_id') ?: null,
            'co_trainer_user_id' => Request::input('co_trainer_user_id') ?: null,
            'preferred_duration_minutes' => (int) Request::input('preferred_duration_minutes', 60),
            'preferred_pitch_kind' => Request::input('preferred_pitch_kind', 'egal'),
            'preferred_size' => Request::input('preferred_size', 'egal'),
            'priority' => (int) Request::input('priority', 5),
            'external_ref' => trim((string) Request::input('external_ref', '')) ?: null,
            'default_pitch_id' => Request::input('default_pitch_id') ?: null,
        ];
    }
}
