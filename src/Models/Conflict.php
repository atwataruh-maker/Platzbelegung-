<?php

namespace App\Models;

class Conflict extends Model
{
    protected static string $table = 'conflicts';

    public static function openForRange(string $start, string $end): array
    {
        $stmt = static::db()->prepare(
            "SELECT c.*, p.name AS pitch_name FROM conflicts c
             LEFT JOIN pitches p ON p.id = c.pitch_id
             WHERE c.status = 'open' AND c.start_datetime < ? AND c.end_datetime > ?
             ORDER BY c.severity = 'red' DESC, c.start_datetime"
        );
        $stmt->execute([$end, $start]);
        return $stmt->fetchAll();
    }

    public static function allOpen(): array
    {
        return static::db()->query(
            "SELECT c.*, p.name AS pitch_name FROM conflicts c
             LEFT JOIN pitches p ON p.id = c.pitch_id
             WHERE c.status = 'open'
             ORDER BY FIELD(c.severity,'red','yellow','green','gray'), c.start_datetime"
        )->fetchAll();
    }

    /** Verhindert doppelte offene Konflikte fuer dieselbe Grundbelegung/Datum. */
    public static function existsOpenFor(?int $baseScheduleId, ?int $gameId, string $occurrenceDate, string $conflictType): bool
    {
        $stmt = static::db()->prepare(
            "SELECT COUNT(*) FROM conflicts
             WHERE status = 'open' AND conflict_type = ? AND occurrence_date = ?
               AND base_schedule_id <=> ? AND game_id <=> ?"
        );
        $stmt->execute([$conflictType, $occurrenceDate, $baseScheduleId, $gameId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function resolve(int $id, ?int $resolvedBy): void
    {
        static::db()->prepare(
            "UPDATE conflicts SET status = 'resolved', resolved_at = NOW(), resolved_by = ? WHERE id = ?"
        )->execute([$resolvedBy, $id]);
    }

    public static function ignore(int $id): void
    {
        static::db()->prepare("UPDATE conflicts SET status = 'ignored' WHERE id = ?")->execute([$id]);
    }
}
