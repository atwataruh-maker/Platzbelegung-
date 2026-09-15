<?php

namespace App\Models;

class ScheduleOverride extends Model
{
    protected static string $table = 'schedule_overrides';

    public static function forBaseScheduleAndDate(int $baseScheduleId, string $date): ?array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM schedule_overrides WHERE base_schedule_id = ? AND occurrence_date = ? LIMIT 1'
        );
        $stmt->execute([$baseScheduleId, $date]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Alle Overrides eines Tages, angereichert mit Mannschafts-/Platznamen und Spielgrund. */
    public static function forDateWithDetails(string $date): array
    {
        $stmt = static::db()->prepare(
            "SELECT so.*, t.name AS team_name, bs.pitch_id AS original_pitch_id, bs.start_time AS original_start_time,
                    bs.end_time AS original_end_time, op.name AS original_pitch_name, np.name AS new_pitch_name,
                    c.game_id, g.opponent AS game_opponent, gt.name AS game_team_name
             FROM schedule_overrides so
             JOIN base_schedules bs ON bs.id = so.base_schedule_id
             JOIN teams t ON t.id = bs.team_id
             JOIN pitches op ON op.id = bs.pitch_id
             JOIN pitches np ON np.id = so.new_pitch_id
             LEFT JOIN conflicts c ON c.id = so.conflict_id
             LEFT JOIN games g ON g.id = c.game_id
             LEFT JOIN teams gt ON gt.id = g.team_id
             WHERE so.occurrence_date = ?
             ORDER BY so.new_start_time"
        );
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array> indiziert nach base_schedule_id */
    public static function mapForDate(string $date): array
    {
        $stmt = static::db()->prepare('SELECT * FROM schedule_overrides WHERE occurrence_date = ?');
        $stmt->execute([$date]);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['base_schedule_id']] = $row;
        }
        return $map;
    }

    public static function upsert(array $data): int
    {
        $existing = self::forBaseScheduleAndDate((int) $data['base_schedule_id'], $data['occurrence_date']);
        if ($existing !== null) {
            static::update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }
        return static::create($data);
    }
}
