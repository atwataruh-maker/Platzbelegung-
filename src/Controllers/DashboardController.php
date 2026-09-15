<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Url;
use App\Core\View;
use App\Models\Conflict;
use App\Models\Game;
use App\Models\Notification;
use App\Models\Team;

class DashboardController
{
    public function index(): void
    {
        // Ohne Login soll die Startseite direkt den oeffentlichen Tagesplan
        // zeigen statt zum Login zu zwingen - der ist fuer Eltern/Fans ohne
        // Account gedacht (siehe PublicViewController).
        if (!Auth::check()) {
            header('Location: ' . Url::to('/public/day'));
            exit;
        }

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
