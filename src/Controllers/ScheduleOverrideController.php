<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Url;
use App\Models\BaseSchedule;
use App\Models\Pitch;
use App\Services\ScheduleOverrideService;
use DateTimeImmutable;

/**
 * AJAX-Endpunkt fuer das Drag&Drop-Verschieben von Trainings im Admin-
 * Wochenplan (siehe public/assets/js/week-editor.js). Reine Session-Auth
 * ueber den normalen Admin-Login, kein separates API-Token noetig.
 */
class ScheduleOverrideController
{
    public function store(): void
    {
        Auth::requireRole('admin');
        header('Content-Type: application/json; charset=utf-8');

        if (!Csrf::verify(Request::input('csrf_token'))) {
            http_response_code(419);
            echo json_encode(['status' => 'error', 'message' => 'Sicherheitspruefung fehlgeschlagen, bitte Seite neu laden.']);
            return;
        }

        $baseScheduleId = (int) Request::input('base_schedule_id');
        $occurrenceDate = (string) Request::input('occurrence_date');
        $newPitchId = (int) Request::input('new_pitch_id');
        $newStartTime = (string) Request::input('new_start_time');
        $newEndTime = (string) Request::input('new_end_time');

        $baseSchedule = BaseSchedule::find($baseScheduleId);
        if ($baseSchedule === null) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Training nicht gefunden.']);
            return;
        }

        $dateObj = DateTimeImmutable::createFromFormat('Y-m-d', $occurrenceDate);
        if ($dateObj === false) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Ungueltiges Datum.']);
            return;
        }

        $pitch = Pitch::find($newPitchId);
        if ($pitch === null || (int) $pitch['is_combo'] === 1) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Ungueltiger Zielplatz (Kombi-Plaetze koennen nicht fuer Training gewaehlt werden).']);
            return;
        }

        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $newStartTime) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $newEndTime)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Ungueltige Uhrzeit.']);
            return;
        }
        if ($newEndTime <= $newStartTime) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Ende muss nach dem Start liegen.']);
            return;
        }

        $overrideId = ScheduleOverrideService::createManualOverride(
            $baseScheduleId,
            $occurrenceDate,
            $newPitchId,
            $newStartTime,
            $newEndTime,
            Auth::id()
        );

        echo json_encode(['status' => 'ok', 'override_id' => $overrideId]);
    }

    public function destroy(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        ScheduleOverrideService::remove((int) $id, Auth::id());

        Flash::set('success', 'Verschiebung rueckgaengig gemacht.');
        header('Location: ' . Url::to((string) Request::input('return_to', '/week')));
        exit;
    }
}
