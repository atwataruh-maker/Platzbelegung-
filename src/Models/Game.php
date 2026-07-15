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

    public static function findByExternalRef(string $externalRef): ?array
    {
        $stmt = static::db()->prepare('SELECT * FROM games WHERE external_ref = ? LIMIT 1');
        $stmt->execute([$externalRef]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Markiert Spiele einer Mannschaft aus einer bestimmten Importquelle als
     * abgesagt, wenn sie beim letzten vollstaendigen Sync nicht mehr
     * vorkamen (z.B. weil DFBnet das Spiel verschoben/geloescht hat).
     * Ruehrt nur zukuenftige, noch nicht manuell veraenderte Importe an.
     *
     * @param string[] $keepExternalRefs external_ref-Werte, die im aktuellen Sync vorkamen
     * @return int[] IDs der neu abgesagten Spiele (damit der Aufrufer die Platzsperren aufheben kann)
     */
    public static function cancelStaleImports(int $teamId, string $source, array $keepExternalRefs): array
    {
        $db = static::db();
        if ($keepExternalRefs === []) {
            $selectSql = "SELECT id FROM games
                    WHERE team_id = ? AND source = ? AND status != 'cancelled' AND kickoff_datetime >= NOW()";
            $params = [$teamId, $source];
        } else {
            $placeholders = implode(',', array_fill(0, count($keepExternalRefs), '?'));
            $selectSql = "SELECT id FROM games
                    WHERE team_id = ? AND source = ? AND status != 'cancelled'
                      AND kickoff_datetime >= NOW() AND external_ref NOT IN ($placeholders)";
            $params = [$teamId, $source, ...$keepExternalRefs];
        }

        $stmt = $db->prepare($selectSql);
        $stmt->execute($params);
        $ids = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        if ($ids === []) {
            return [];
        }

        $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("UPDATE games SET status = 'cancelled' WHERE id IN ($idPlaceholders)")->execute($ids);
        return $ids;
    }
}
