<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Url;
use App\Core\View;
use App\Models\ChangeLog;
use App\Models\Conflict;
use App\Models\FallbackSuggestion;
use App\Services\ConflictService;
use App\Services\ScheduleOverrideService;

class ConflictController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        $conflicts = Conflict::allOpen();
        $suggestions = [];
        foreach ($conflicts as $conflict) {
            $suggestions[$conflict['id']] = FallbackSuggestion::forConflict((int) $conflict['id']);
        }
        View::render('conflicts/index', ['conflicts' => $conflicts, 'suggestions' => $suggestions]);
    }

    /** Ein-Klick: Ausweichvorschlag uebernehmen - Training wird tatsaechlich verschoben. */
    public function applySuggestion(string $suggestionId): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $overrideId = ScheduleOverrideService::applyFallbackSuggestion((int) $suggestionId, Auth::id());

        if ($overrideId === null) {
            Flash::set('error', 'Ausweichvorschlag nicht gefunden.');
        } else {
            Flash::set('success', 'Ausweichvorschlag uebernommen, Training wurde verschoben.');
        }

        header('Location: ' . Url::to('/admin/conflicts'));
        exit;
    }

    public function resolve(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $note = trim((string) Request::input('note', ''));
        Conflict::resolve((int) $id, Auth::id());
        ChangeLog::record(Auth::id(), 'conflict', (int) $id, 'resolved', $note !== '' ? $note : 'Manuell durch Admin geloest');

        Flash::set('success', 'Konflikt als geloest markiert.');
        header('Location: ' . Url::to('/admin/conflicts'));
        exit;
    }

    public function ignore(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        Conflict::ignore((int) $id);
        ChangeLog::record(Auth::id(), 'conflict', (int) $id, 'ignored', 'Konflikt als nicht relevant markiert');

        Flash::set('success', 'Konflikt ignoriert.');
        header('Location: ' . Url::to('/admin/conflicts'));
        exit;
    }

    public function rescan(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        ConflictService::rescanHorizon();

        Flash::set('success', 'Konflikte wurden neu berechnet.');
        header('Location: ' . Url::to('/admin/conflicts'));
        exit;
    }
}
