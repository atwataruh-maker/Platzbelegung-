<?php

namespace App\Services;

use App\Core\Database;
use App\Models\FallbackSuggestion;
use App\Models\Notification;
use App\Models\Pitch;
use App\Models\PitchBlock;
use App\Models\PitchRelation;
use App\Models\TrainerConfirmation;
use PDO;

/**
 * Abschnitt 7: automatische Ausweichvorschlaege.
 * Sucht fuer eine verdraengte Trainingszeit einen freien, passenden
 * Ausweichplatz zur selben Uhrzeit (gleiche Saison, kompatible Groesse/Art,
 * keine Halle/Aussen-Mischung ausser erlaubt, niemals gesperrte Plaetze).
 */
class FallbackService
{
    /**
     * @return int|null ID der erzeugten fallback_suggestion oder null, falls
     *                   keine Alternative gefunden wurde (dann wurde stattdessen
     *                   ein 'no_alternative'-Konflikt angelegt).
     */
    public static function resolveConflict(int $conflictId, array $baseSchedule, string $occurrenceDate, string $start, string $end): ?int
    {
        $originalPitch = Pitch::find((int) $baseSchedule['pitch_id']);
        if ($originalPitch === null) {
            return null;
        }

        $candidates = self::candidatePitches($originalPitch, $baseSchedule);
        $weekday = (int) $baseSchedule['weekday'];

        foreach ($candidates as $candidate) {
            if (self::isPitchFree((int) $candidate['id'], $start, $end, (int) $baseSchedule['id'], $baseSchedule['season_id'], $weekday)) {
                $suggestionId = FallbackSuggestion::create([
                    'conflict_id' => $conflictId,
                    'base_schedule_id' => $baseSchedule['id'],
                    'occurrence_date' => $occurrenceDate,
                    'suggested_pitch_id' => $candidate['id'],
                    'suggested_start' => $start,
                    'suggested_end' => $end,
                ]);

                self::createConfirmationsAndNotify((int) $suggestionId, $baseSchedule, $candidate, $occurrenceDate, $start, $end);
                return (int) $suggestionId;
            }
        }

        return null;
    }

    /** Kandidatenliste, sortiert nach bester Passung (Art, Groesse, Reihenfolge). */
    private static function candidatePitches(array $originalPitch, array $baseSchedule): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM pitches WHERE active = 1 AND season_type = ? AND id != ?'
        );
        $stmt->execute([$originalPitch['season_type'], $originalPitch['id']]);
        $all = $stmt->fetchAll();

        $allowMixHallOutdoor = false; // Halle/Aussen nicht automatisch mischen (Abschnitt 7)
        $isHallOriginal = $originalPitch['kind'] === 'halle';

        $preferredKind = $baseSchedule['preferred_pitch_kind'] ?? 'egal';
        $preferredSize = $baseSchedule['preferred_size'] ?? 'egal';

        $filtered = array_values(array_filter($all, static function (array $p) use ($isHallOriginal, $allowMixHallOutdoor) {
            $isHallCandidate = $p['kind'] === 'halle';
            if (!$allowMixHallOutdoor && $isHallCandidate !== $isHallOriginal) {
                return false;
            }
            return true;
        }));

        usort($filtered, static function (array $a, array $b) use ($originalPitch, $preferredKind, $preferredSize) {
            $scoreA = self::matchScore($a, $originalPitch, $preferredKind, $preferredSize);
            $scoreB = self::matchScore($b, $originalPitch, $preferredKind, $preferredSize);
            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }
            return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
        });

        return $filtered;
    }

    private static function matchScore(array $candidate, array $originalPitch, string $preferredKind, string $preferredSize): int
    {
        $score = 0;
        if ($candidate['kind'] === $originalPitch['kind']) {
            $score += 2;
        }
        if ($preferredKind !== 'egal' && $candidate['kind'] === $preferredKind) {
            $score += 3;
        }
        if ($preferredSize !== 'egal') {
            if ($candidate['size'] === $preferredSize) {
                $score += 2;
            } elseif ($preferredSize === 'drittel' && $candidate['size'] === 'gross') {
                // Kleinfeldmannschaften koennen leichter auf ein Drittel/Grossfeld ausweichen
                $score += 1;
            }
        }
        return $score;
    }

    /** Prueft, ob ein Kandidatenplatz (inkl. Teilflaechen) zur gewuenschten Zeit frei ist. */
    private static function isPitchFree(int $pitchId, string $start, string $end, int $excludeBaseScheduleId, int $seasonId, int $weekday): bool
    {
        $relatedIds = PitchRelation::relatedPitchIds($pitchId);

        if (PitchBlock::overlapping($relatedIds, $start, $end) !== []) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($relatedIds), '?'));
        $sql = "SELECT COUNT(*) FROM base_schedules
                WHERE active = 1 AND season_id = ? AND weekday = ? AND id != ?
                  AND pitch_id IN ($placeholders)
                  AND start_time < ? AND end_time > ?";
        $stmt = Database::connection()->prepare($sql);
        $startTime = substr($start, 11, 8);
        $endTime = substr($end, 11, 8);
        $stmt->execute([$seasonId, $weekday, $excludeBaseScheduleId, ...$relatedIds, $endTime, $startTime]);

        return (int) $stmt->fetchColumn() === 0;
    }

    private static function createConfirmationsAndNotify(int $suggestionId, array $baseSchedule, array $candidatePitch, string $occurrenceDate, string $start, string $end): void
    {
        $stmt = Database::connection()->prepare(
            'SELECT DISTINCT u.id, u.name, u.email FROM users u
             JOIN teams t ON (t.trainer_user_id = u.id OR t.co_trainer_user_id = u.id)
             WHERE t.id = ? AND u.active = 1'
        );
        $stmt->execute([$baseSchedule['team_id']]);
        $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $startTime = substr($start, 11, 5);
        $endTime = substr($end, 11, 5);
        $dateFormatted = (new \DateTimeImmutable($occurrenceDate))->format('d.m.Y');

        foreach ($trainers as $trainer) {
            $confirmationId = TrainerConfirmation::create([
                'fallback_suggestion_id' => $suggestionId,
                'user_id' => $trainer['id'],
            ]);

            $body = "Am {$dateFormatted} ist euer gewohnter Platz wegen eines Spiels belegt.\n" .
                "Euer Training wurde automatisch auf {$candidatePitch['name']} von {$startTime}-{$endTime} Uhr verschoben.\n" .
                "Bitte im Trainerbereich bestaetigen oder ablehnen.";

            Notification::create([
                'user_id' => $trainer['id'],
                'subject' => 'Training verschoben: ' . $dateFormatted,
                'body' => $body,
                'related_type' => 'trainer_confirmation',
                'related_id' => $confirmationId,
            ]);
        }
    }
}
