<?php

namespace App\Services;

use App\Models\ChangeLog;
use App\Models\Game;
use App\Models\Team;

/**
 * Verarbeitet Spieldaten aus einer maschinellen Quelle (z.B. DFBnet-Export).
 * Quellen-unabhaengig: der Aufrufer (z.B. ImportController) liefert bereits
 * normalisierte Datensaetze, unabhaengig davon ob sie per DFBnet-Automation,
 * Fussball.de oder einem spaeteren CSV-Upload gewonnen wurden.
 *
 * Erwartetes Format je Eintrag in $entries:
 *   team_external_ref?: string  (bevorzugt, siehe teams.external_ref)
 *   team_name?: string          (Fallback, falls keine external_ref hinterlegt ist)
 *   external_ref: string        (stabile Kennung des Spiels, z.B. DFBnet-Spielnummer)
 *   opponent: string
 *   is_home: bool
 *   kickoff_datetime: string    ('Y-m-d H:i:s')
 *   competition?: string        (pflichtspiel|pokal|nachholspiel|jugendspiel|freundschaft)
 *   venue_text?: string         (roher Spielort-Text aus der Quelle, nur zur Anzeige)
 *   cancelled?: bool
 */
class GameImportService
{
    private const ALLOWED_COMPETITIONS = ['pflichtspiel', 'pokal', 'nachholspiel', 'jugendspiel', 'freundschaft'];

    public static function import(array $entries, string $source): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $unmatchedTeams = [];
        $touchedGameIds = [];
        $keepRefsByTeam = [];

        foreach ($entries as $entry) {
            $team = self::resolveTeam($entry);
            if ($team === null) {
                $unmatchedTeams[] = $entry['team_external_ref'] ?? $entry['team_name'] ?? '(unbekannt)';
                $skipped++;
                continue;
            }

            $externalRef = trim((string) ($entry['external_ref'] ?? ''));
            if ($externalRef === '' || empty($entry['kickoff_datetime'])) {
                $skipped++;
                continue;
            }

            $keepRefsByTeam[$team['id']][] = $externalRef;

            $isHome = !empty($entry['is_home']);
            $competition = in_array($entry['competition'] ?? '', self::ALLOWED_COMPETITIONS, true)
                ? $entry['competition']
                : 'pflichtspiel';

            $notesParts = ["Importiert aus " . strtoupper($source)];
            if (!empty($entry['venue_text'])) {
                $notesParts[] = 'Spielort lt. Quelle: ' . trim((string) $entry['venue_text']);
            }

            $data = [
                'team_id' => $team['id'],
                'opponent' => trim((string) ($entry['opponent'] ?? '')),
                'is_home' => $isHome ? 1 : 0,
                'kickoff_datetime' => $entry['kickoff_datetime'],
                'competition' => $competition,
                'status' => !empty($entry['cancelled']) ? 'cancelled' : 'scheduled',
                'source' => $source,
                'external_ref' => $externalRef,
                'notes' => implode(' | ', $notesParts),
            ];

            // Heimspiel ohne explizit zugewiesenen Platz: Standard-Heimplatz der
            // Mannschaft verwenden, damit Platzsperre/Konflikterkennung sofort greifen.
            if ($isHome && !empty($team['default_pitch_id'])) {
                $data['pitch_id'] = $team['default_pitch_id'];
            }

            $existing = Game::findByExternalRef($externalRef);
            if ($existing !== null) {
                // Manuell nachbearbeitete Spiele (z.B. Admin hat Platz korrigiert)
                // nicht von einem erneuten Import ueberschreiben lassen, ausser
                // bei Zeit/Status-Aenderungen, die aus der Quelle kommen.
                unset($data['pitch_id']);
                Game::update((int) $existing['id'], $data);
                $touchedGameIds[] = (int) $existing['id'];
                $updated++;
            } else {
                $newId = Game::create($data);
                ChangeLog::record(null, 'game', $newId, 'imported', "Spiel per {$source}-Import angelegt: {$data['opponent']}");
                $touchedGameIds[] = $newId;
                $created++;
            }
        }

        $cancelledCount = 0;
        foreach ($keepRefsByTeam as $teamId => $refs) {
            $cancelledIds = Game::cancelStaleImports($teamId, $source, $refs);
            foreach ($cancelledIds as $cancelledId) {
                ChangeLog::record(null, 'game', $cancelledId, 'cancelled', "Spiel im {$source}-Import nicht mehr vorhanden, automatisch abgesagt");
            }
            $touchedGameIds = array_merge($touchedGameIds, $cancelledIds);
            $cancelledCount += count($cancelledIds);
        }

        foreach ($touchedGameIds as $gameId) {
            $game = Game::find($gameId);
            if ($game !== null) {
                BlockingService::syncForGame($game);
            }
        }

        if ($touchedGameIds !== []) {
            ConflictService::rescanHorizon();
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'cancelled' => $cancelledCount,
            'skipped' => $skipped,
            'unmatched_teams' => array_values(array_unique($unmatchedTeams)),
        ];
    }

    private static function resolveTeam(array $entry): ?array
    {
        if (!empty($entry['team_external_ref'])) {
            $team = Team::findByExternalRef((string) $entry['team_external_ref']);
            if ($team !== null) {
                return $team;
            }
        }
        if (!empty($entry['team_name'])) {
            return Team::findByName((string) $entry['team_name']);
        }
        return null;
    }
}
