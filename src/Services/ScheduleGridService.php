<?php

namespace App\Services;

use App\Core\Database;
use App\Models\BaseSchedule;
use App\Models\Pitch;
use App\Models\PitchBlock;
use App\Models\ScheduleOverride;
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
        // uasort statt usort: behaelt die Pitch-ID als Schluessel, damit
        // $pitches gleichzeitig als sortierte Spaltenliste UND als
        // id->Platz-Nachschlagetabelle (fuer Verschiebungs-Hinweise) dient.
        uasort($pitches, static fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        $trainings = [];
        foreach ($seasonIds as $seasonId) {
            foreach (BaseSchedule::forWeekday($seasonId, $weekday) as $bs) {
                $trainings[] = $bs;
            }
        }

        $dayStart = "{$date} 00:00:00";
        $dayEnd = "{$date} 23:59:59";
        $blocks = PitchBlock::forDateRange($dayStart, $dayEnd);
        $overridesByBaseSchedule = ScheduleOverride::mapForDate($date);

        $slots = self::buildSlots($date, $trainings, $blocks, $overridesByBaseSchedule);
        $cells = [];

        foreach ($pitches as $pitch) {
            foreach ($slots as $slot) {
                $cells[$pitch['id']][$slot['start']] = self::resolveCell($pitch, $slot, $trainings, $blocks, $overridesByBaseSchedule, $pitches);
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

    private static function resolveCell(array $pitch, array $slot, array $trainings, array $blocks, array $overridesByBaseSchedule, array $pitchesById): array
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

        // Urspruengliches Trainingsvorkommen an dieser Stelle (auch wenn es
        // inzwischen per Override an einen anderen Platz verschoben wurde).
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
        $override = $trainingMatch !== null ? ($overridesByBaseSchedule[(int) $trainingMatch['id']] ?? null) : null;

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
                'id' => (int) $blockMatch['id'],
                'reason' => $blockMatch['reason'],
                'label' => $label,
                'note' => $blockMatch['note'],
                // Welche Mannschaft durch das Spiel/die Sperre den Platz raeumen muss ...
                'displaced_team' => $trainingMatch['team_name'] ?? null,
                // ... und wohin sie ausgewichen ist, falls bereits geklaert.
                'moved_to' => self::describeOverrideTarget($override, $pitchesById),
            ];
        }

        // Ist an diesem Platz/Slot ein von ANDERSWO hierher verschobenes Training?
        foreach ($trainings as $candidate) {
            $candidateOverride = $overridesByBaseSchedule[(int) $candidate['id']] ?? null;
            if ($candidateOverride === null) {
                continue;
            }
            if ((int) $candidateOverride['new_pitch_id'] !== (int) $pitch['id']) {
                continue;
            }
            $newStart = substr($candidateOverride['new_start_time'], 0, 5);
            $newEnd = substr($candidateOverride['new_end_time'], 0, 5);
            if ($slot['start'] < $newEnd && $newStart < $slot['end']) {
                $originalPitch = $pitchesById[(int) $candidate['pitch_id']] ?? null;
                return [
                    'type' => 'training',
                    'label' => $candidate['team_name'],
                    'team_id' => (int) $candidate['team_id'],
                    'base_schedule_id' => (int) $candidate['id'],
                    'duration_minutes' => self::minutesBetween($candidateOverride['new_start_time'], $candidateOverride['new_end_time']),
                    'moved' => true,
                    'moved_from' => ($originalPitch['name'] ?? '?') . ', ' . substr($candidate['start_time'], 0, 5) . ' Uhr',
                    'moved_reason' => $candidateOverride['reason'],
                    'override_id' => (int) $candidateOverride['id'],
                ];
            }
        }

        if ($trainingMatch !== null && $override === null) {
            return [
                'type' => 'training',
                'label' => $trainingMatch['team_name'],
                'team_id' => (int) $trainingMatch['team_id'],
                'base_schedule_id' => (int) $trainingMatch['id'],
                'duration_minutes' => self::minutesBetween($trainingMatch['start_time'], $trainingMatch['end_time']),
            ];
        }

        if ($trainingMatch !== null && $override !== null) {
            // Training war urspruenglich hier geplant, ist aber verschoben - Platz ist frei,
            // aber ein Hinweis zeigt an, wohin es verlegt wurde.
            return [
                'type' => 'free',
                'label' => '',
                'moved_away_text' => $trainingMatch['team_name'] . ' verschoben auf ' . self::describeOverrideTarget($override, $pitchesById),
            ];
        }

        return ['type' => 'free', 'label' => ''];
    }

    private static function describeOverrideTarget(?array $override, array $pitchesById): ?string
    {
        if ($override === null) {
            return null;
        }
        $pitch = $pitchesById[(int) $override['new_pitch_id']] ?? null;
        $pitchName = $pitch['name'] ?? "Platz-ID {$override['new_pitch_id']}";
        return $pitchName . ', ' . substr($override['new_start_time'], 0, 5) . '-' . substr($override['new_end_time'], 0, 5) . ' Uhr';
    }

    private static function minutesBetween(string $start, string $end): int
    {
        return self::toMinutes(substr($end, 0, 5)) - self::toMinutes(substr($start, 0, 5));
    }

    /**
     * Zeitbereich fuer die Slots: kompakter Standardbereich, automatisch
     * erweitert um alle tatsaechlich vorkommenden Trainings-/Sperrzeiten
     * dieses Tages, damit nie echte Daten abgeschnitten werden.
     *
     * @return array<int,array{start:string,end:string}>
     */
    private static function buildSlots(string $date, array $trainings, array $blocks, array $overridesByBaseSchedule = []): array
    {
        $startMinutes = self::toMinutes(self::DEFAULT_DAY_START);
        $endMinutes = self::toMinutes(self::DEFAULT_DAY_END);

        foreach ($trainings as $training) {
            $startMinutes = min($startMinutes, self::toMinutes(substr($training['start_time'], 0, 5)));
            $endMinutes = max($endMinutes, self::toMinutes(substr($training['end_time'], 0, 5)));
        }

        foreach ($overridesByBaseSchedule as $override) {
            $startMinutes = min($startMinutes, self::toMinutes(substr($override['new_start_time'], 0, 5)));
            $endMinutes = max($endMinutes, self::toMinutes(substr($override['new_end_time'], 0, 5)));
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

    /**
     * Fasst aufeinanderfolgende Slots mit identischem Inhalt zu einem
     * zusammenhaengenden Belegungsblock zusammen (fuer die Kartendarstellung
     * im Raster: ein Training/Spiel = ein Block ueber mehrere Zeilen, statt
     * einer Zelle pro 30-Minuten-Slot).
     *
     * @return array<int,array<int,array>> Platz-ID => Liste von Events, je
     *   mit 'start_index' (0-basiert in $grid['slots']) und 'span' (Anzahl Slots)
     */
    public static function buildEvents(array $grid): array
    {
        $events = [];
        $slotCount = count($grid['slots']);

        foreach ($grid['pitches'] as $pitch) {
            $pitchId = $pitch['id'];
            $pitchEvents = [];
            $i = 0;
            while ($i < $slotCount) {
                $cell = $grid['cells'][$pitchId][$grid['slots'][$i]['start']];
                if ($cell['type'] === 'free' && empty($cell['moved_away_text'])) {
                    $i++;
                    continue;
                }
                $key = self::eventKey($cell);
                $span = 1;
                $j = $i + 1;
                while ($j < $slotCount) {
                    $nextCell = $grid['cells'][$pitchId][$grid['slots'][$j]['start']];
                    if (self::eventKey($nextCell) !== $key) {
                        break;
                    }
                    $span++;
                    $j++;
                }
                $pitchEvents[] = $cell + ['start_index' => $i, 'span' => $span];
                $i = $j;
            }
            $events[$pitchId] = $pitchEvents;
        }

        return $events;
    }

    private static function eventKey(array $cell): string
    {
        return match ($cell['type']) {
            'training' => 'training:' . ($cell['base_schedule_id'] ?? '') . ':' . (empty($cell['moved']) ? '0' : '1'),
            'block' => 'block:' . ($cell['id'] ?? ''),
            'free' => 'note:' . ($cell['moved_away_text'] ?? ''),
            default => 'other',
        };
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
