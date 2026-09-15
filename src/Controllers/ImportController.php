<?php

namespace App\Controllers;

use App\Core\Config;
use App\Models\DfbnetCredential;
use App\Services\GameImportService;

/**
 * Maschine-zu-Maschine-Schnittstelle fuer den automatischen Spielplan-Import
 * (z.B. aus dem DFBnet-Automatisierungsskript, siehe automation/dfbnet-import).
 * Bewusst ohne Session-Login: Absicherung ueber ein per Header uebergebenes
 * Geheimtoken (config: import.api_token), das mit einem GitHub-Actions-Secret
 * uebereinstimmt.
 */
class ImportController
{
    private const ALLOWED_SOURCES = ['dfbnet', 'csv'];

    public function games(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->hasValidToken()) {
            http_response_code(401);
            echo json_encode(['error' => 'unauthorized']);
            return;
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode((string) $raw, true);

        if (!is_array($payload) || !isset($payload['games']) || !is_array($payload['games'])) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_payload', 'message' => 'Erwartet wird {"source": "dfbnet", "games": [...]}']);
            return;
        }

        $source = is_string($payload['source'] ?? null) ? $payload['source'] : 'dfbnet';
        if (!in_array($source, self::ALLOWED_SOURCES, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_source', 'allowed' => self::ALLOWED_SOURCES]);
            return;
        }

        $result = GameImportService::import($payload['games'], $source);

        http_response_code(200);
        echo json_encode(['status' => 'ok'] + $result);
    }

    /**
     * Liefert die vom Admin unter /admin/dfbnet hinterlegten DFBnet-
     * Zugangsdaten an die Import-Automatisierung (GitHub Action). Gleiche
     * Token-Absicherung wie /import/games; das Passwort verlaesst die App
     * ausschliesslich ueber diesen Endpunkt und nur entschluesselt fuer
     * den unmittelbaren Login-Vorgang der Automatisierung.
     */
    public function dfbnetCredentials(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->hasValidToken()) {
            http_response_code(401);
            echo json_encode(['error' => 'unauthorized']);
            return;
        }

        $credential = DfbnetCredential::get();
        $password = $credential !== null ? DfbnetCredential::decryptedPassword() : null;

        if ($credential === null || $password === null) {
            http_response_code(404);
            echo json_encode([
                'error' => 'not_configured',
                'message' => 'Keine DFBnet-Zugangsdaten hinterlegt. Bitte im Adminbereich unter /admin/dfbnet eintragen.',
            ]);
            return;
        }

        http_response_code(200);
        echo json_encode(['username' => $credential['username'], 'password' => $password]);
    }

    private function hasValidToken(): bool
    {
        $expected = (string) Config::get('import.api_token', '');
        if ($expected === '' || $expected === 'bitte-zufaelligen-token-erzeugen') {
            return false;
        }

        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_IMPORT_TOKEN'] ?? '';
        $provided = str_starts_with($header, 'Bearer ') ? substr($header, 7) : $header;

        return $provided !== '' && hash_equals($expected, $provided);
    }
}
