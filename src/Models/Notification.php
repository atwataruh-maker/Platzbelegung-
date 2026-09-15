<?php

namespace App\Models;

class Notification extends Model
{
    protected static string $table = 'notifications';

    public static function pending(int $limit = 100): array
    {
        $stmt = static::db()->prepare(
            "SELECT n.*, u.email, u.name AS user_name FROM notifications n
             JOIN users u ON u.id = n.user_id
             WHERE n.status = 'pending' ORDER BY n.created_at LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function markSent(int $id): void
    {
        static::db()->prepare("UPDATE notifications SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$id]);
    }

    public static function markFailed(int $id): void
    {
        static::db()->prepare("UPDATE notifications SET status = 'failed' WHERE id = ?")->execute([$id]);
    }

    public static function forUser(int $userId, int $limit = 20): array
    {
        $stmt = static::db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
