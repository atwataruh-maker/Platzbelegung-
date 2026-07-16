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
    // Kompakter Standardbereich (in der Praxis findet vor 15 Uhr kein
    // Training/Spiel statt). Wird automatisch erweitert, falls echte Daten
    // (z.B. eine fruehe Vorlaufzeit vor einem Spiel) ausserhalb liegen -
    // es wird also nie etwas abgeschnitten.
    private const DEFAULT_DAY_START = '15:00';
    private const DEFAULT_DAY_END = '22:00';

    public static function buildDayGrid(string $date): array
    {
        $weekday = (int) (new DateTimeImmutable($date))->format('N');
        $seasonIds = self::activeSeasonIdsFor($date);
        $seasonTypes = self::activeSeasonTypesFor($date);

        $pitches = [];
        foreach ($seasonTypes as $type) {
            foreach (Pitch::bookableForSeasonType($type) as $pitch) {
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

        $slots = self::buildSlots($date, $trainings, $blocks);
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
        $blockMatch = null;
        foreach ($blocks as $block) {
            if ((int) $block['pitch_id'] !== (int) $pitch['id']) {
                continue;
            }
            if ($slot['start'] < substr($block['end_datetime'], 11, 5) && substr($block['start_datetime'], 11, 5) < $slot['end']) {
                $blockMatch = $block;
                break;
            }
        }

        $trainingMatch = null;
        foreach ($trainings as $training) {
            if ((int) $training['pitch_id'] !== (int) $pitch['id']) {
                continue;
            }
            if ($slot['start'] < $training['end_time'] && $training['start_time'] < $slot['end']) {
                $trainingMatch = $training;
                break;
            }
        }

        if ($blockMatch !== null) {
            $label = $blockMatch['reason'] === 'game' ? 'Spiel' : ucfirst($blockMatch['reason']);
            if (!empty($blockMatch['game_team_name'])) {
                $label = $blockMatch['game_team_name'] . ' - Spiel';
                if (!empty($blockMatch['game_opponent'])) {
                    $label .= ' vs. ' . $blockMatch['game_opponent'];
                }
            }
            return [
                'type' => 'block',
                'reason' => $blockMatch['reason'],
                'label' => $label,
                'note' => $blockMatch['note'],
                // Welche Mannschaft durch das Spiel/die Sperre den Platz raeumen muss.
                'displaced_team' => $trainingMatch['team_name'] ?? null,
            ];
        }

        if ($trainingMatch !== null) {
            return [
                'type' => 'training',
                'label' => $trainingMatch['team_name'],
                'team_id' => (int) $trainingMatch['team_id'],
            ];
        }

        return ['type' => 'free', 'label' => ''];
    }

    /**
     * Zeitbereich fuer die Slots: kompakter Standardbereich, automatisch
     * erweitert um alle tatsaechlich vorkommenden Trainings-/Sperrzeiten
     * dieses Tages, damit nie echte Daten abgeschnitten werden.
     *
     * @return array<int,array{start:string,end:string}>
     */
    private static function buildSlots(string $date, array $trainings, array $blocks): array
    {
        $startMinutes = self::toMinutes(self::DEFAULT_DAY_START);
        $endMinutes = self::toMinutes(self::DEFAULT_DAY_END);

        foreach ($trainings as $training) {
            $startMinutes = min($startMinutes, self::toMinutes(substr($training['start_time'], 0, 5)));
            $endMinutes = max($endMinutes, self::toMinutes(substr($training['end_time'], 0, 5)));
        }

        foreach ($blocks as $block) {
            $blockStartDate = substr($block['start_datetime'], 0, 10);
            $blockEndDate = substr($block['end_datetime'], 0, 10);
            $startTime = $blockStartDate === $date ? substr($block['start_datetime'], 11, 5) : '00:00';
            $endTime = $blockEndDate === $date ? substr($block['end_datetime'], 11, 5) : '23:59';
            $startMinutes = min($startMinutes, self::toMinutes($startTime));
            $endMinutes = max($endMinutes, self::toMinutes($endTime));
        }

        $startMinutes = intdiv($startMinutes, self::SLOT_MINUTES) * self::SLOT_MINUTES;
        $endMinutes = (int) ceil($endMinutes / self::SLOT_MINUTES) * self::SLOT_MINUTES;

        $slots = [];
        for ($minutes = $startMinutes; $minutes < $endMinutes; $minutes += self::SLOT_MINUTES) {
            $slots[] = ['start' => self::fromMinutes($minutes), 'end' => self::fromMinutes($minutes + self::SLOT_MINUTES)];
        }
        return $slots;
    }

    private static function toMinutes(string $hhmm): int
    {
        [$h, $m] = explode(':', $hhmm);
        return ((int) $h) * 60 + (int) $m;
    }

    private static function fromMinutes(int $minutes): string
    {
        $minutes = (($minutes % 1440) + 1440) % 1440;
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
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
