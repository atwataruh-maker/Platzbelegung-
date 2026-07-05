<?php

namespace App\Models;

class FallbackSuggestion extends Model
{
    protected static string $table = 'fallback_suggestions';

    public static function forConflict(int $conflictId): array
    {
        $stmt = static::db()->prepare(
            'SELECT fs.*, p.name AS pitch_name FROM fallback_suggestions fs
             JOIN pitches p ON p.id = fs.suggested_pitch_id
             WHERE fs.conflict_id = ? ORDER BY fs.created_at'
        );
        $stmt->execute([$conflictId]);
        return $stmt->fetchAll();
    }

    public static function pendingForTeam(int $teamId): array
    {
        $stmt = static::db()->prepare(
            "SELECT fs.*, p.name AS pitch_name, c.pitch_id AS original_pitch_id, op.name AS original_pitch_name
             FROM fallback_suggestions fs
             JOIN conflicts c ON c.id = fs.conflict_id
             JOIN pitches p ON p.id = fs.suggested_pitch_id
             LEFT JOIN pitches op ON op.id = c.pitch_id
             LEFT JOIN base_schedules bs ON bs.id = fs.base_schedule_id
             WHERE fs.status = 'pending' AND bs.team_id = ?
             ORDER BY fs.occurrence_date"
        );
        $stmt->execute([$teamId]);
        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        static::db()->prepare('UPDATE fallback_suggestions SET status = ? WHERE id = ?')->execute([$status, $id]);
    }
}
