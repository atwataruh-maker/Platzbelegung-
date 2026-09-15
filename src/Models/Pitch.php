<?php

namespace App\Models;

class Pitch extends Model
{
    protected static string $table = 'pitches';

    /** Nur einzeln bespielbare Plaetze (ohne Kombi-Plaetze) - fuer die Wochen-/Tagesplan-Spalten. */
    public static function bookableForSeasonType(string $seasonType): array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM pitches WHERE season_type = ? AND active = 1 AND is_combo = 0 ORDER BY sort_order, name'
        );
        $stmt->execute([$seasonType]);
        return $stmt->fetchAll();
    }

    /** Alle aktiven Plaetze inkl. Kombi-Plaetze - fuer Auswahllisten (Mannschaften, Spiele). */
    public static function allActive(): array
    {
        return static::db()->query('SELECT * FROM pitches WHERE active = 1 ORDER BY is_combo, season_type, sort_order, name')->fetchAll();
    }

    /** Alle aktiven, einzeln bespielbaren Plaetze ohne Kombi-Plaetze - fuer die Grundbelegung (Training). */
    public static function allBookable(): array
    {
        return static::db()->query(
            'SELECT * FROM pitches WHERE active = 1 AND is_combo = 0 ORDER BY season_type, sort_order, name'
        )->fetchAll();
    }
}
