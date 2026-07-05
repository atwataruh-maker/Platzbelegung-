<?php

namespace App\Models;

class BaseSchedule extends Model
{
    protected static string $table = 'base_schedules';

    public static function allWithNames(?int $seasonId = null): array
    {
        $sql = 'SELECT bs.*, t.name AS team_name, t.priority AS team_priority,
                       p.name AS pitch_name, p.kind AS pitch_kind, p.season_type
                FROM base_schedules bs
                JOIN teams t ON t.id = bs.team_id
                JOIN pitches p ON p.id = bs.pitch_id
                WHERE bs.active = 1';
        $params = [];
        if ($seasonId !== null) {
            $sql .= ' AND bs.season_id = :season_id';
            $params['season_id'] = $seasonId;
        }
        $sql .= ' ORDER BY bs.weekday, bs.start_time';
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Grundbelegungen, die an einem bestimmten Wochentag innerhalb einer Saison aktiv sind. */
    public static function forWeekday(int $seasonId, int $weekday): array
    {
        $stmt = static::db()->prepare(
            'SELECT bs.*, t.name AS team_name, t.priority AS team_priority, t.trainer_user_id, t.co_trainer_user_id,
                    t.preferred_pitch_kind, t.preferred_size, t.preferred_duration_minutes,
                    p.name AS pitch_name, p.kind AS pitch_kind
             FROM base_schedules bs
             JOIN teams t ON t.id = bs.team_id
             JOIN pitches p ON p.id = bs.pitch_id
             WHERE bs.active = 1 AND bs.season_id = ? AND bs.weekday = ?
             ORDER BY bs.start_time'
        );
        $stmt->execute([$seasonId, $weekday]);
        return $stmt->fetchAll();
    }

    public static function forTeam(int $teamId): array
    {
        $stmt = static::db()->prepare(
            'SELECT bs.*, p.name AS pitch_name FROM base_schedules bs
             JOIN pitches p ON p.id = bs.pitch_id
             WHERE bs.team_id = ? AND bs.active = 1 ORDER BY bs.weekday, bs.start_time'
        );
        $stmt->execute([$teamId]);
        return $stmt->fetchAll();
    }
}
