<?php

namespace App\Services;

use App\Core\Database;
use App\Models\BaseSchedule;
use App\Models\Pitch;
use App\Models\PitchBlock;
use DateTimeImmutable;
use PDO;

/**
 * Baut die Daten fuer Tages-/Wochenansicht: Plaetze als Spalten, 30-Minuten-
 * Raster als Zeilen, farblich je Mannschaft, Spiele/Sperren hervorgehoben
 * (Abschnitt 5 und 11).
 */
class ScheduleGridService
{
    private const SLOT_MINUTES = 30;
    private const DAY_START = '08:00';
    private const DAY_END = '22:00';

    public static function buildDayGrid(string $date): array
    {
        $weekday = (int) (new DateTimeImmutable($date))->format('N');
        $seasonIds = self::activeSeasonIdsFor($date);
        $seasonTypes = self::activeSeasonTypesFor($date);

        $pitches = [];
        foreach ($seasonTypes as $type) {
            foreach (Pitch::forSeasonType($type) as $pitch) {
                $pitches[$pitch['id']] = $pitch;
            }
        }
        usort($pitches, static fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        $trainings = [];
        foreach ($seasonIds as $seasonId) {
            foreach (BaseSchedule::forWeekday($seasonId, $weekday) as $bs) {
                $trainings[] = $bs;
            }
        }

        $dayStart = "{$date} 00:00:00";
        $dayEnd = "{$date} 23:59:59";
        $blocks = PitchBlock::forDateRange($dayStart, $dayEnd);

        $slots = self::buildSlots();
        $cells = [];

        foreach ($pitches as $pitch) {
            foreach ($slots as $slot) {
                $cells[$pitch['id']][$slot['start']] = self::resolveCell($pitch, $slot, $trainings, $blocks);
            }
        }

        return [
            'date' => $date,
            'weekday' => $weekday,
            'pitches' => array_values($pitches),
            'slots' => $slots,
            'cells' => $cells,
        ];
    }

    private static function resolveCell(array $pitch, array $slot, array $trainings, array $blocks): array
    {
        foreach ($blocks as $block) {
            if ((int) $block['pitch_id'] !== (int) $pitch['id']) {
                continue;
            }
            if ($slot['start'] < substr($block['end_datetime'], 11, 5) && substr($block['start_datetime'], 11, 5) < $slot['end']) {
                return [
                    'type' => 'block',
                    'reason' => $block['reason'],
                    'label' => $block['reason'] === 'game' ? 'Spiel' : ucfirst($block['reason']),
                    'note' => $block['note'],
                ];
            }
        }

        foreach ($trainings as $training) {
            if ((int) $training['pitch_id'] !== (int) $pitch['id']) {
                continue;
            }
            if ($slot['start'] < $training['end_time'] && $training['start_time'] < $slot['end']) {
                return [
                    'type' => 'training',
                    'label' => $training['team_name'],
                    'team_id' => (int) $training['team_id'],
                ];
            }
        }

        return ['type' => 'free', 'label' => ''];
    }

    /** @return array<int,array{start:string,end:string}> */
    private static function buildSlots(): array
    {
        $slots = [];
        $current = DateTimeImmutable::createFromFormat('H:i', self::DAY_START);
        $end = DateTimeImmutable::createFromFormat('H:i', self::DAY_END);
        while ($current < $end) {
            $next = $current->modify('+' . self::SLOT_MINUTES . ' minutes');
            $slots[] = ['start' => $current->format('H:i'), 'end' => $next->format('H:i')];
            $current = $next;
        }
        return $slots;
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

    /** @return string[] */
    private static function activeSeasonTypesFor(string $date): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT DISTINCT type FROM seasons WHERE type IN ('summer','winter','hall') AND ? BETWEEN start_date AND end_date"
        );
        $stmt->execute([$date]);
        $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $types !== [] ? $types : ['summer'];
    }

    public static function teamColor(int $teamId): string
    {
        $hue = ($teamId * 47) % 360;
        return "hsl({$hue}, 62%, 42%)";
    }
}
