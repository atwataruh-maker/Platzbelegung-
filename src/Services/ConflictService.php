<?php

namespace App\Services;

use App\Core\Database;
use App\Models\BaseSchedule;
use App\Models\ChangeLog;
use App\Models\Conflict;
use App\Models\Notification;
use App\Models\PitchBlock;
use App\Models\PitchRelation;
use App\Models\Season;
use App\Models\User;
use DateInterval;
use DateTimeImmutable;
use PDO;

/**
 * Abschnitt 9: automatische Konflikterkennung.
 * Prueft fuer einen Zeitraum, ob Trainingszeiten aus der Grundbelegung mit
 * Platzsperren (v.a. durch Spiele) oder anderen Trainings kollidieren, legt
 * dafuer Konflikt-Datensaetze an und stoesst Ausweichvorschlaege an.
 */
class ConflictService
{
    public static function scanRange(string $startDate, string $endDate): void
    {
        self::autoResolveStaleConflicts();

        $period = new \DatePeriod(
            new DateTimeImmutable($startDate),
            new DateInterval('P1D'),
            (new DateTimeImmutable($endDate))->modify('+1 day')
        );

        foreach ($period as $day) {
            self::scanDate($day->format('Y-m-d'));
        }
    }

    private static function scanDate(string $date): void
    {
        $weekday = (int) (new DateTimeImmutable($date))->format('N');
        $seasonIds = self::activeSeasonIdsFor($date);
        if ($seasonIds === []) {
            return;
        }

        $occurrences = [];
        foreach ($seasonIds as $seasonId) {
            foreach (BaseSchedule::forWeekday($seasonId, $weekday) as $bs) {
                $occurrences[] = $bs;
            }
        }

        foreach ($occurrences as $bs) {
            self::checkTrainingVsBlock($bs, $date);
        }

        self::checkDoubleBookings($occurrences, $date);
    }

    /** @return int[] */
    private static function activeSeasonIdsFor(string $date): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT id FROM seasons WHERE type IN ('summer','winter','hall') AND ? BETWEEN start_date AND end_date"
        );
        $stmt->execute([$date]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private static function checkTrainingVsBlock(array $bs, string $date): void
    {
        $start = "{$date} {$bs['start_time']}";
        $end = "{$date} {$bs['end_time']}";
        $relatedIds = PitchRelation::relatedPitchIds((int) $bs['pitch_id']);

        $blocks = PitchBlock::overlapping($relatedIds, $start, $end);
        if ($blocks === []) {
            return;
        }

        if (Conflict::existsOpenFor((int) $bs['id'], null, $date, 'training_vs_block')) {
            return;
        }

        $conflictId = Conflict::create([
            'conflict_type' => 'training_vs_block',
            'pitch_id' => $bs['pitch_id'],
            'occurrence_date' => $date,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'base_schedule_id' => $bs['id'],
            'severity' => 'red',
            'description' => "Training von {$bs['team_name']} kollidiert mit Platzsperre auf {$bs['pitch_name']}.",
        ]);

        $suggestionId = FallbackService::resolveConflict((int) $conflictId, $bs, $date, $start, $end);

        if ($suggestionId !== null) {
            Conflict::update((int) $conflictId, ['severity' => 'yellow']);
            return;
        }

        Conflict::create([
            'conflict_type' => 'no_alternative',
            'pitch_id' => $bs['pitch_id'],
            'occurrence_date' => $date,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'base_schedule_id' => $bs['id'],
            'severity' => 'red',
            'description' => "Keine Ausweichmoeglichkeit fuer {$bs['team_name']} am {$date} gefunden. Bitte manuell klaeren.",
        ]);

        self::notifyAdmins(
            'Kein Ausweichplatz gefunden',
            "Fuer {$bs['team_name']} konnte am {$date} ({$bs['start_time']}-{$bs['end_time']}) kein Ausweichplatz " .
            "gefunden werden, da {$bs['pitch_name']} durch ein Spiel belegt ist. Bitte im Adminbereich pruefen."
        );
    }

    private static function checkDoubleBookings(array $occurrences, string $date): void
    {
        $count = count($occurrences);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $occurrences[$i];
                $b = $occurrences[$j];
                if ((int) $a['team_id'] === (int) $b['team_id']) {
                    continue;
                }

                $relatedA = PitchRelation::relatedPitchIds((int) $a['pitch_id']);
                if (!in_array((int) $b['pitch_id'], $relatedA, true)) {
                    continue;
                }
                if ($a['start_time'] >= $b['end_time'] || $b['start_time'] >= $a['end_time']) {
                    continue;
                }

                if (Conflict::existsOpenFor((int) $a['id'], null, $date, 'double_booking')) {
                    continue;
                }

                Conflict::create([
                    'conflict_type' => 'double_booking',
                    'pitch_id' => $a['pitch_id'],
                    'occurrence_date' => $date,
                    'start_datetime' => "{$date} {$a['start_time']}",
                    'end_datetime' => "{$date} {$a['end_time']}",
                    'base_schedule_id' => $a['id'],
                    'severity' => 'red',
                    'description' => "{$a['team_name']} und {$b['team_name']} sind am {$date} gleichzeitig auf " .
                        "{$a['pitch_name']}/{$b['pitch_name']} eingeplant.",
                ]);
            }
        }
    }

    /** Schliesst automatisch Konflikte, deren urspruengliche Ursache (Sperre) nicht mehr besteht. */
    private static function autoResolveStaleConflicts(): void
    {
        foreach (Conflict::allOpen() as $conflict) {
            if ($conflict['conflict_type'] !== 'training_vs_block') {
                continue;
            }
            $relatedIds = $conflict['pitch_id'] ? PitchRelation::relatedPitchIds((int) $conflict['pitch_id']) : [];
            $stillBlocked = $relatedIds !== [] && PitchBlock::overlapping($relatedIds, $conflict['start_datetime'], $conflict['end_datetime']) !== [];
            if (!$stillBlocked) {
                Conflict::resolve((int) $conflict['id'], null);
                ChangeLog::record(null, 'conflict', (int) $conflict['id'], 'auto_resolved', 'Platzsperre besteht nicht mehr, Konflikt automatisch geschlossen.');
            }
        }
    }

    private static function notifyAdmins(string $subject, string $body): void
    {
        foreach (User::admins() as $admin) {
            Notification::create([
                'user_id' => $admin['id'],
                'subject' => $subject,
                'body' => $body,
                'related_type' => 'conflict',
            ]);
        }
    }
}
