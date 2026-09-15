<?php

namespace App\Models;

class PitchBlock extends Model
{
    protected static string $table = 'pitch_blocks';

    public static function deleteForGame(int $gameId): void
    {
        static::db()->prepare('DELETE FROM pitch_blocks WHERE game_id = ?')->execute([$gameId]);
    }

    /** Alle Sperren, die einen der angegebenen Plaetze im Zeitraum betreffen. */
    public static function overlapping(array $pitchIds, string $start, string $end): array
    {
        if ($pitchIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($pitchIds), '?'));
        $sql = "SELECT * FROM pitch_blocks
                WHERE pitch_id IN ($placeholders)
                  AND start_datetime < ? AND end_datetime > ?
                ORDER BY start_datetime";
        $stmt = static::db()->prepare($sql);
        $stmt->execute([...$pitchIds, $end, $start]);
        return $stmt->fetchAll();
    }

    public static function forDateRange(string $start, string $end): array
    {
        $stmt = static::db()->prepare(
            'SELECT pb.*, p.name AS pitch_name, g.opponent AS game_opponent, g.team_id AS game_team_id,
                    gt.name AS game_team_name
             FROM pitch_blocks pb
             JOIN pitches p ON p.id = pb.pitch_id
             LEFT JOIN games g ON g.id = pb.game_id
             LEFT JOIN teams gt ON gt.id = g.team_id
             WHERE pb.start_datetime < ? AND pb.end_datetime > ?
             ORDER BY pb.start_datetime'
        );
        $stmt->execute([$end, $start]);
        return $stmt->fetchAll();
    }
}
