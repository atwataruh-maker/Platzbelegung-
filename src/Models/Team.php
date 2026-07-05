<?php

namespace App\Models;

class Team extends Model
{
    protected static string $table = 'teams';

    public static function allWithTrainers(): array
    {
        return static::db()->query(
            'SELECT t.*, u.name AS trainer_name, u.email AS trainer_email,
                    c.name AS co_trainer_name
             FROM teams t
             LEFT JOIN users u ON u.id = t.trainer_user_id
             LEFT JOIN users c ON c.id = t.co_trainer_user_id
             WHERE t.active = 1
             ORDER BY t.priority ASC, t.name ASC'
        )->fetchAll();
    }

    /** Alle Nutzer (Haupttrainer, Co-Trainer, weitere Zuordnungen), die ueber diese Mannschaft informiert werden sollen. */
    public static function notifiableUsers(int $teamId): array
    {
        $stmt = static::db()->prepare(
            'SELECT DISTINCT u.* FROM users u
             LEFT JOIN teams t ON (t.trainer_user_id = u.id OR t.co_trainer_user_id = u.id) AND t.id = :team_id
             LEFT JOIN team_user tu ON tu.user_id = u.id AND tu.team_id = :team_id2
             WHERE (t.id IS NOT NULL OR tu.team_id IS NOT NULL) AND u.active = 1'
        );
        $stmt->execute(['team_id' => $teamId, 'team_id2' => $teamId]);
        return $stmt->fetchAll();
    }

    public static function forTrainer(int $userId): array
    {
        $stmt = static::db()->prepare(
            'SELECT DISTINCT t.* FROM teams t
             LEFT JOIN team_user tu ON tu.team_id = t.id
             WHERE t.active = 1 AND (t.trainer_user_id = :uid OR t.co_trainer_user_id = :uid2 OR tu.user_id = :uid3)
             ORDER BY t.name'
        );
        $stmt->execute(['uid' => $userId, 'uid2' => $userId, 'uid3' => $userId]);
        return $stmt->fetchAll();
    }
}
