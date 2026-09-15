<?php

namespace App\Services;

use App\Models\ChangeLog;
use App\Models\Conflict;
use App\Models\FallbackSuggestion;
use App\Models\Notification;
use App\Models\Pitch;
use App\Models\ScheduleOverride;
use App\Models\User;

/**
 * Zentrale Stelle, die ein Trainingsvorkommen tatsaechlich auf einen
 * anderen Platz/Zeit "verschiebt" (schedule_overrides), egal ob dies durch
 * Uebernahme eines automatischen Ausweichvorschlags (Admin oder Trainer)
 * oder durch manuelles Drag&Drop im Wochenplan ausgeloest wird.
 */
class ScheduleOverrideService
{
    /** Uebernimmt einen automatisch erzeugten Ausweichvorschlag 1:1 als Verschiebung. */
    public static function applyFallbackSuggestion(int $fallbackSuggestionId, ?int $actingUserId): ?int
    {
        $suggestion = FallbackSuggestion::find($fallbackSuggestionId);
        if ($suggestion === null) {
            return null;
        }

        $overrideId = ScheduleOverride::upsert([
            'base_schedule_id' => $suggestion['base_schedule_id'],
            'occurrence_date' => $suggestion['occurrence_date'],
            'new_pitch_id' => $suggestion['suggested_pitch_id'],
            'new_start_time' => substr($suggestion['suggested_start'], 11, 8),
            'new_end_time' => substr($suggestion['suggested_end'], 11, 8),
            'reason' => 'game_conflict',
            'conflict_id' => $suggestion['conflict_id'],
            'created_by' => $actingUserId,
        ]);

        FallbackSuggestion::setStatus($fallbackSuggestionId, 'accepted');
        if (!empty($suggestion['conflict_id'])) {
            Conflict::resolve((int) $suggestion['conflict_id'], $actingUserId);
        }

        ChangeLog::record(
            $actingUserId,
            'schedule_override',
            $overrideId,
            'applied_suggestion',
            "Ausweichvorschlag uebernommen, Training verschoben auf Platz-ID {$suggestion['suggested_pitch_id']} am {$suggestion['occurrence_date']}"
        );

        ConflictService::rescanHorizon();
        return $overrideId;
    }

    /** Lehnt einen Ausweichvorschlag ab und informiert den Admin, damit manuell nachgesteuert wird. */
    public static function declineFallbackSuggestion(int $fallbackSuggestionId, ?int $actingUserId): void
    {
        $suggestion = FallbackSuggestion::find($fallbackSuggestionId);
        if ($suggestion === null) {
            return;
        }

        FallbackSuggestion::setStatus($fallbackSuggestionId, 'rejected');
        ChangeLog::record($actingUserId, 'fallback_suggestion', $fallbackSuggestionId, 'declined', 'Trainer hat Ausweichvorschlag abgelehnt');

        foreach (User::admins() as $admin) {
            Notification::create([
                'user_id' => $admin['id'],
                'subject' => 'Ausweichvorschlag abgelehnt',
                'body' => "Ein Trainer hat einen Ausweichvorschlag fuer den {$suggestion['occurrence_date']} abgelehnt. Bitte im Konfliktbereich manuell klaeren.",
                'related_type' => 'fallback_suggestion',
                'related_id' => $fallbackSuggestionId,
            ]);
        }
    }

    /** Manuelle Verschiebung, z.B. per Drag&Drop im Wochenplan. */
    public static function createManualOverride(
        int $baseScheduleId,
        string $date,
        int $newPitchId,
        string $newStartTime,
        string $newEndTime,
        ?int $actingUserId
    ): int {
        $overrideId = ScheduleOverride::upsert([
            'base_schedule_id' => $baseScheduleId,
            'occurrence_date' => $date,
            'new_pitch_id' => $newPitchId,
            'new_start_time' => $newStartTime,
            'new_end_time' => $newEndTime,
            'reason' => 'manual',
            'conflict_id' => null,
            'created_by' => $actingUserId,
        ]);

        $pitch = Pitch::find($newPitchId);
        ChangeLog::record(
            $actingUserId,
            'schedule_override',
            $overrideId,
            'manual_move',
            "Training am {$date} manuell auf " . ($pitch['name'] ?? "Platz-ID {$newPitchId}") . " ({$newStartTime}-{$newEndTime}) verschoben"
        );

        ConflictService::rescanHorizon();
        return $overrideId;
    }

    /** Macht eine Verschiebung rueckgaengig (Training laeuft wieder nach Grundbelegung). */
    public static function remove(int $overrideId, ?int $actingUserId): void
    {
        ScheduleOverride::delete($overrideId);
        ChangeLog::record($actingUserId, 'schedule_override', $overrideId, 'removed', 'Verschiebung rueckgaengig gemacht');
        ConflictService::rescanHorizon();
    }
}
