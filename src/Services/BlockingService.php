<?php

namespace App\Services;

use App\Core\Config;
use App\Models\ChangeLog;
use App\Models\PitchBlock;
use App\Models\PitchRelation;
use DateTimeImmutable;

/**
 * Regel aus Abschnitt 6 der Anforderungen: Spiele haben Vorrang vor Training.
 * Diese Klasse berechnet aus einem Spiel automatisch die Sperrzeit
 * (Vorlauf + Spieldauer + Nachlauf) und sperrt den Platz sowie alle
 * zugehoerigen Teilflaechen.
 */
class BlockingService
{
    /** Legt/aktualisiert die automatischen Platzsperren fuer ein Heimspiel an. */
    public static function syncForGame(array $game, ?int $actingUserId = null): void
    {
        PitchBlock::deleteForGame((int) $game['id']);

        if ($game['status'] === 'cancelled' || (int) $game['is_home'] !== 1 || empty($game['pitch_id'])) {
            return;
        }

        [$start, $end] = self::computeWindow($game);
        $relatedPitchIds = PitchRelation::relatedPitchIds((int) $game['pitch_id']);

        foreach ($relatedPitchIds as $pitchId) {
            PitchBlock::create([
                'pitch_id' => $pitchId,
                'start_datetime' => $start,
                'end_datetime' => $end,
                'reason' => 'game',
                'game_id' => $game['id'],
                'note' => 'Automatisch gesperrt wegen Spiel gegen ' . $game['opponent'],
                'created_by' => $actingUserId,
            ]);
        }

        ChangeLog::record(
            $actingUserId,
            'game',
            (int) $game['id'],
            'block_synced',
            "Platzsperre {$start} bis {$end} fuer Platz-ID {$game['pitch_id']} (inkl. Teilflaechen) erzeugt"
        );
    }

    /** @return array{0:string,1:string} [start, end] als 'Y-m-d H:i:s' */
    public static function computeWindow(array $game): array
    {
        $lead = (int) ($game['lead_minutes'] ?? Config::get('rules.default_lead_minutes', 60));
        $lag = (int) ($game['lag_minutes'] ?? Config::get('rules.default_lag_minutes', 30));
        $duration = (int) ($game['duration_minutes'] ?? Config::get('rules.default_duration_minutes', 90));

        $kickoff = new DateTimeImmutable($game['kickoff_datetime']);
        $start = $kickoff->modify("-{$lead} minutes");
        $end = $kickoff->modify("+{$duration} minutes")->modify("+{$lag} minutes");

        return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
    }
}
