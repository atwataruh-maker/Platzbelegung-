<?php

namespace App\Models;

/**
 * Verwaltet Teilflaechen-Beziehungen zwischen Plaetzen. Ein "ganzer" Platz
 * (parent) kann aus mehreren Teilflaechen (child) bestehen. Eine Belegung
 * des parent muss automatisch alle childs sperren und umgekehrt muss eine
 * Belegung eines child auch den parent (und ggf. Geschwister-Teilflaechen)
 * als teilbelegt behandeln - fuer die MVP-Logik wird vereinfachend jede
 * Belegung eines beliebigen Elements der Gruppe als Sperre der gesamten
 * Gruppe interpretiert, sofern es sich um ein Vollfeld-Spiel handelt.
 */
class PitchRelation extends Model
{
    protected static string $table = 'pitch_relations';

    public static function childrenOf(int $parentPitchId): array
    {
        $stmt = static::db()->prepare(
            'SELECT p.* FROM pitches p
             JOIN pitch_relations r ON r.child_pitch_id = p.id
             WHERE r.parent_pitch_id = ?'
        );
        $stmt->execute([$parentPitchId]);
        return $stmt->fetchAll();
    }

    public static function parentsOf(int $childPitchId): array
    {
        $stmt = static::db()->prepare(
            'SELECT p.* FROM pitches p
             JOIN pitch_relations r ON r.parent_pitch_id = p.id
             WHERE r.child_pitch_id = ?'
        );
        $stmt->execute([$childPitchId]);
        return $stmt->fetchAll();
    }

    /**
     * Liefert die IDs des Platzes selbst plus aller direkt verwandten
     * Plaetze (Eltern und Kinder), da eine Belegung eines Elements
     * automatisch alle anderen betroffenen Flaechen mitsperrt.
     *
     * Bewusst OHNE Geschwister-Ausweitung (Kind -> Elternteil -> andere
     * Kinder): bei Kombi-Plaetzen (z.B. "KuRa Grossfeld" aus KuRa 1 + KuRa 2)
     * sind die einzelnen Teilplaetze ausserhalb eines Grossfeldspiels
     * unabhaengig voneinander bespielbar. Wird der Kombi-Platz selbst belegt,
     * kaskadiert das ueber childrenOf() bereits korrekt auf beide realen
     * Plaetze (siehe BlockingService) - dafuer ist diese Geschwisterlogik
     * nicht noetig und wuerde sonst faelschlich gleichzeitiges, unabhaengiges
     * Training auf den Teilplaetzen als Doppelbelegung markieren.
     *
     * @return int[]
     */
    public static function relatedPitchIds(int $pitchId): array
    {
        $ids = [$pitchId];
        foreach (self::childrenOf($pitchId) as $child) {
            $ids[] = (int) $child['id'];
        }
        foreach (self::parentsOf($pitchId) as $parent) {
            $ids[] = (int) $parent['id'];
        }
        return array_values(array_unique($ids));
    }

    public static function link(int $parentPitchId, int $childPitchId): void
    {
        $stmt = static::db()->prepare(
            'INSERT IGNORE INTO pitch_relations (parent_pitch_id, child_pitch_id) VALUES (?, ?)'
        );
        $stmt->execute([$parentPitchId, $childPitchId]);
    }

    public static function unlink(int $parentPitchId, int $childPitchId): void
    {
        $stmt = static::db()->prepare(
            'DELETE FROM pitch_relations WHERE parent_pitch_id = ? AND child_pitch_id = ?'
        );
        $stmt->execute([$parentPitchId, $childPitchId]);
    }
}
