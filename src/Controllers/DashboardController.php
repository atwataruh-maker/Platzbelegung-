<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Conflict;
use App\Models\Game;
use App\Models\Notification;
use App\Models\Team;

class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        if ($user['role'] === 'admin') {
            View::render('dashboard/admin', [
                'openConflicts' => Conflict::allOpen(),
                'upcomingGames' => Game::upcomingHomeGames(14),
            ]);
            return;
        }

        $teams = Team::forTrainer((int) $user['id']);
        View::render('dashboard/trainer', [
            'teams' => $teams,
            'notifications' => Notification::forUser((int) $user['id'], 10),
        ]);
    }
}
