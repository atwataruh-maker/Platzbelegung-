<?php

namespace App\Models;

class Game extends Model
{
    protected static string $table = 'games';

    public static function allWithNames(): array
    {
        return static::db()->query(
            "SELECT g.*, t.name AS team_name, p.name AS pitch_name
             FROM games g
             JOIN teams t ON t.id = g.team_id
             LEFT JOIN pitches p ON p.id = g.pitch_id
             ORDER BY g.kickoff_datetime DESC"
        )->fetchAll();
    }

    public static function upcomingHomeGames(int $days): array
    {
        $stmt = static::db()->prepare(
            "SELECT g.*, t.name AS team_name, p.name AS pitch_name
             FROM games g
             JOIN teams t ON t.id = g.team_id
             LEFT JOIN pitches p ON p.id = g.pitch_id
             WHERE g.is_home = 1 AND g.status != 'cancelled'
               AND g.kickoff_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)
             ORDER BY g.kickoff_datetime"
        );
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll();
    }

    public static function forTeam(int $teamId, int $limit = 10): array
    {
        $stmt = static::db()->prepare(
            "SELECT g.*, p.name AS pitch_name FROM games g
             LEFT JOIN pitches p ON p.id = g.pitch_id
             WHERE g.team_id = ? AND g.status != 'cancelled' AND g.kickoff_datetime >= NOW()
             ORDER BY g.kickoff_datetime LIMIT ?"
        );
        $stmt->bindValue(1, $teamId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
