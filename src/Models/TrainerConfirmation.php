<?php

namespace App\Models;

class TrainerConfirmation extends Model
{
    protected static string $table = 'trainer_confirmations';

    public static function respond(int $id, int $userId, string $status): bool
    {
        $stmt = static::db()->prepare(
            "UPDATE trainer_confirmations SET status = ?, responded_at = NOW()
             WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([$status, $id, $userId]);
    }
}
