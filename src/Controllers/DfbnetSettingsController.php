<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Url;
use App\Core\View;
use App\Models\ChangeLog;
use App\Models\DfbnetCredential;
use App\Models\User;

/**
 * Verwaltung der DFBnet-Zugangsdaten fuer den automatischen Spielplan-
 * Import. Das Passwort wird nur entgegengenommen und verschluesselt
 * gespeichert (siehe DfbnetCredential/App\Core\Crypto) - es wird nirgends
 * wieder im Klartext angezeigt oder geloggt.
 */
class DfbnetSettingsController
{
    public function index(): void
    {
        Auth::requireRole('admin');

        $credential = DfbnetCredential::get();
        $updatedByName = null;
        if ($credential !== null && !empty($credential['updated_by'])) {
            $updatedBy = User::find((int) $credential['updated_by']);
            $updatedByName = $updatedBy['name'] ?? null;
        }

        View::render('dfbnet/index', [
            'credential' => $credential,
            'updatedByName' => $updatedByName,
        ]);
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $username = trim((string) Request::input('dfbnet_username', ''));
        $password = (string) Request::input('dfbnet_password', '');

        if ($username === '' || $password === '') {
            Flash::set('error', 'Bitte Benutzername und Passwort angeben.');
            header('Location: ' . Url::to('/admin/dfbnet'));
            exit;
        }

        DfbnetCredential::save($username, $password, Auth::id());
        ChangeLog::record(
            Auth::id(),
            'dfbnet_credentials',
            1,
            'updated',
            "DFBnet-Zugangsdaten aktualisiert (Benutzername: {$username})"
        );

        Flash::set('success', 'DFBnet-Zugangsdaten gespeichert.');
        header('Location: ' . Url::to('/admin/dfbnet'));
        exit;
    }
}
