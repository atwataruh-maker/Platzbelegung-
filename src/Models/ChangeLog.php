<?php

namespace App\Models;

class ChangeLog extends Model
{
    protected static string $table = 'change_log';

    public static function record(?int $userId, string $entityType, int $entityId, string $action, string $description = ''): void
    {
        static::create([
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'description' => $description,
        ]);
    }

    public static function recent(int $limit = 50): array
    {
        $stmt = static::db()->prepare(
            'SELECT cl.*, u.name AS user_name FROM change_log cl
             LEFT JOIN users u ON u.id = cl.user_id
             ORDER BY cl.created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
