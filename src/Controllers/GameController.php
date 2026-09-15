<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Url;
use App\Core\View;
use App\Models\ChangeLog;
use App\Models\Game;
use App\Models\Pitch;
use App\Models\Team;
use App\Services\BlockingService;
use App\Services\ConflictService;

class GameController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        View::render('games/index', [
            'games' => Game::allWithNames(),
            'teams' => Team::allWithTrainers(),
            'pitches' => Pitch::allActive(),
        ]);
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $data = $this->extractData();
        $id = Game::create($data);
        ChangeLog::record(Auth::id(), 'game', $id, 'created', "Spiel gegen {$data['opponent']} angelegt");

        $this->syncAndRescan($id);

        Flash::set('success', 'Spiel angelegt. Platzsperren und Konflikte wurden automatisch berechnet.');
        header('Location: ' . Url::to('/admin/games'));
        exit;
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $data = $this->extractData();
        Game::update((int) $id, $data);
        ChangeLog::record(Auth::id(), 'game', (int) $id, 'updated', 'Spiel geaendert');

        $this->syncAndRescan((int) $id);

        Flash::set('success', 'Spiel aktualisiert. Platzsperren und Konflikte wurden automatisch neu berechnet.');
        header('Location: ' . Url::to('/admin/games'));
        exit;
    }

    public function cancel(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        Game::update((int) $id, ['status' => 'cancelled']);
        ChangeLog::record(Auth::id(), 'game', (int) $id, 'cancelled', 'Spiel abgesagt, Platzsperre wird aufgehoben');

        $this->syncAndRescan((int) $id);

        Flash::set('success', 'Spiel abgesagt. Platzsperre wurde aufgehoben.');
        header('Location: ' . Url::to('/admin/games'));
        exit;
    }

    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        Game::delete((int) $id);
        ChangeLog::record(Auth::id(), 'game', (int) $id, 'deleted', 'Spiel geloescht');

        Flash::set('success', 'Spiel geloescht.');
        header('Location: ' . Url::to('/admin/games'));
        exit;
    }

    private function syncAndRescan(int $gameId): void
    {
        $game = Game::find($gameId);
        if ($game === null) {
            return;
        }
        BlockingService::syncForGame($game, Auth::id());

        ConflictService::rescanHorizon();
    }

    private function extractData(): array
    {
        return [
            'team_id' => (int) Request::input('team_id'),
            'opponent' => trim((string) Request::input('opponent')),
            'is_home' => Request::input('is_home') ? 1 : 0,
            'kickoff_datetime' => Request::input('kickoff_datetime'),
            'pitch_id' => Request::input('pitch_id') ?: null,
            'competition' => Request::input('competition', 'pflichtspiel'),
            'duration_minutes' => Request::input('duration_minutes') ?: null,
            'lead_minutes' => Request::input('lead_minutes') ?: null,
            'lag_minutes' => Request::input('lag_minutes') ?: null,
            'notes' => trim((string) Request::input('notes', '')) ?: null,
        ];
    }
}
