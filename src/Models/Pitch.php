<?php

namespace App\Models;

class Pitch extends Model
{
    protected static string $table = 'pitches';

    public static function forSeasonType(string $seasonType): array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM pitches WHERE season_type = ? AND active = 1 ORDER BY sort_order, name'
        );
        $stmt->execute([$seasonType]);
        return $stmt->fetchAll();
    }

    public static function allActive(): array
    {
        return static::db()->query('SELECT * FROM pitches WHERE active = 1 ORDER BY season_type, sort_order, name')->fetchAll();
    }
}
