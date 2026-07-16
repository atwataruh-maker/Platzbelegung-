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
use App\Models\PitchRelation;

class PitchController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        $pitches = Pitch::allActive();
        $relations = [];
        foreach ($pitches as $pitch) {
            $relations[$pitch['id']] = PitchRelation::childrenOf((int) $pitch['id']);
        }
        View::render('pitches/index', ['pitches' => $pitches, 'relations' => $relations]);
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $data = $this->extractData();
        $id = Pitch::create($data);
        ChangeLog::record(Auth::id(), 'pitch', $id, 'created', "Platz '{$data['name']}' angelegt");

        Flash::set('success', 'Platz angelegt.');
        header('Location: ' . Url::to('/admin/pitches'));
        exit;
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $data = $this->extractData();
        Pitch::update((int) $id, $data);
        ChangeLog::record(Auth::id(), 'pitch', (int) $id, 'updated', "Platz '{$data['name']}' geaendert");

        Flash::set('success', 'Platz aktualisiert.');
        header('Location: ' . Url::to('/admin/pitches'));
        exit;
    }

    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        Pitch::update((int) $id, ['active' => 0]);
        ChangeLog::record(Auth::id(), 'pitch', (int) $id, 'deactivated', 'Platz deaktiviert');

        Flash::set('success', 'Platz deaktiviert.');
        header('Location: ' . Url::to('/admin/pitches'));
        exit;
    }

    public function linkRelation(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $childId = (int) Request::input('child_pitch_id');
        if ($childId > 0 && $childId !== (int) $id) {
            PitchRelation::link((int) $id, $childId);
            ChangeLog::record(Auth::id(), 'pitch', (int) $id, 'relation_linked', "Teilflaeche {$childId} verknuepft");
        }

        Flash::set('success', 'Teilflaeche verknuepft.');
        header('Location: ' . Url::to('/admin/pitches'));
        exit;
    }

    public function unlinkRelation(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $childId = (int) Request::input('child_pitch_id');
        PitchRelation::unlink((int) $id, $childId);
        ChangeLog::record(Auth::id(), 'pitch', (int) $id, 'relation_unlinked', "Teilflaeche {$childId} entfernt");

        Flash::set('success', 'Verknuepfung entfernt.');
        header('Location: ' . Url::to('/admin/pitches'));
        exit;
    }

    private function extractData(): array
    {
        return [
            'name' => trim((string) Request::input('name')),
            'season_type' => Request::input('season_type', 'summer'),
            'kind' => Request::input('kind', 'rasen'),
            'size' => Request::input('size', 'gross'),
            'sort_order' => (int) Request::input('sort_order', 0),
            'is_combo' => Request::input('is_combo') ? 1 : 0,
        ];
    }
}
