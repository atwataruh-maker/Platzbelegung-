<?php

namespace App\Models;

class Season extends Model
{
    protected static string $table = 'seasons';

    private const BASE_TYPES = ['summer', 'winter', 'hall'];

    /**
     * Ermittelt die aktuell gueltige Basis-Saison (Sommer/Winter/Halle):
     * zunaechst per Datum, sonst per manuell gesetztem is_active-Flag.
     */
    public static function current(): ?array
    {
        $stmt = static::db()->query(
            "SELECT * FROM seasons WHERE type IN ('summer','winter','hall')
             AND CURDATE() BETWEEN start_date AND end_date
             ORDER BY id DESC LIMIT 1"
        );
        $row = $stmt->fetch();
        if ($row !== false) {
            return $row;
        }

        $stmt = static::db()->query(
            "SELECT * FROM seasons WHERE type IN ('summer','winter','hall') AND is_active = 1 LIMIT 1"
        );
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Aktiviert eine Basis-Saison und deaktiviert alle anderen Basis-Saisons. */
    public static function activate(int $id): void
    {
        $db = static::db();
        $db->beginTransaction();
        $placeholders = implode(',', array_fill(0, count(self::BASE_TYPES), '?'));
        $db->prepare("UPDATE seasons SET is_active = 0 WHERE type IN ($placeholders)")
            ->execute(self::BASE_TYPES);
        $db->prepare('UPDATE seasons SET is_active = 1 WHERE id = ?')->execute([$id]);
        $db->commit();
    }

    public static function specialModeFor(string $date): ?array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM seasons WHERE type IN ('holiday','tournament','closed')
             AND ? BETWEEN start_date AND end_date ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$date]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
