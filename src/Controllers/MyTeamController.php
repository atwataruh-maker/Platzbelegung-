<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;
use App\Models\BaseSchedule;
use App\Models\ChangeLog;
use App\Models\FallbackSuggestion;
use App\Models\Game;
use App\Models\Team;
use App\Models\TrainerConfirmation;
use App\Services\ScheduleOverrideService;

class MyTeamController
{
    public function index(): void
    {
        Auth::requireRole('admin', 'trainer', 'betreuer');
        $userId = Auth::id();

        $teams = Team::forTrainer($userId);
        $data = [];
        foreach ($teams as $team) {
            $data[] = [
                'team' => $team,
                'schedule' => BaseSchedule::forTeam((int) $team['id']),
                'games' => Game::forTeam((int) $team['id']),
                'suggestions' => FallbackSuggestion::pendingForTeam((int) $team['id']),
            ];
        }

        View::render('my_team/index', [
            'teamsData' => $data,
            'pendingConfirmations' => $this->pendingConfirmationsForUser($userId),
        ]);
    }

    public function confirm(string $confirmationId): void
    {
        Auth::requireRole('admin', 'trainer', 'betreuer');
        Csrf::requireValid();

        $userId = Auth::id();
        TrainerConfirmation::respond((int) $confirmationId, $userId, 'confirmed');
        ChangeLog::record($userId, 'trainer_confirmation', (int) $confirmationId, 'confirmed', 'Trainer hat Ausweichvorschlag bestaetigt');

        $fsId = $this->fallbackSuggestionIdFor((int) $confirmationId);
        if ($fsId !== null) {
            ScheduleOverrideService::applyFallbackSuggestion($fsId, $userId);
        }

        Flash::set('success', 'Ausweichvorschlag bestaetigt, Training wurde verschoben.');
        header('Location: ' . Url::to('/my-team'));
        exit;
    }

    public function decline(string $confirmationId): void
    {
        Auth::requireRole('admin', 'trainer', 'betreuer');
        Csrf::requireValid();

        $userId = Auth::id();
        TrainerConfirmation::respond((int) $confirmationId, $userId, 'declined');
        ChangeLog::record($userId, 'trainer_confirmation', (int) $confirmationId, 'declined', 'Trainer hat Ausweichvorschlag abgelehnt');

        $fsId = $this->fallbackSuggestionIdFor((int) $confirmationId);
        if ($fsId !== null) {
            ScheduleOverrideService::declineFallbackSuggestion($fsId, $userId);
        }

        Flash::set('success', 'Ausweichvorschlag abgelehnt. Der Admin wurde informiert.');
        header('Location: ' . Url::to('/my-team'));
        exit;
    }

    private function fallbackSuggestionIdFor(int $confirmationId): ?int
    {
        $stmt = Database::connection()->prepare('SELECT fallback_suggestion_id FROM trainer_confirmations WHERE id = ?');
        $stmt->execute([$confirmationId]);
        $fsId = $stmt->fetchColumn();
        return $fsId === false ? null : (int) $fsId;
    }

    private function pendingConfirmationsForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT tc.*, fs.occurrence_date, fs.suggested_start, fs.suggested_end, p.name AS pitch_name
             FROM trainer_confirmations tc
             JOIN fallback_suggestions fs ON fs.id = tc.fallback_suggestion_id
             JOIN pitches p ON p.id = fs.suggested_pitch_id
             WHERE tc.user_id = ? AND tc.status = 'pending'
             ORDER BY fs.occurrence_date"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
