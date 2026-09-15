<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Url;
use App\Core\View;
use App\Models\ChangeLog;
use App\Models\Season;

class SeasonController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        View::render('seasons/index', ['seasons' => Season::all('start_date DESC')]);
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        $data = [
            'name' => trim((string) Request::input('name')),
            'type' => Request::input('type', 'summer'),
            'start_date' => Request::input('start_date'),
            'end_date' => Request::input('end_date'),
        ];
        $id = Season::create($data);
        ChangeLog::record(Auth::id(), 'season', $id, 'created', "Saison '{$data['name']}' angelegt");

        Flash::set('success', 'Saison angelegt.');
        header('Location: ' . Url::to('/admin/seasons'));
        exit;
    }

    public function activate(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();

        Season::activate((int) $id);
        ChangeLog::record(Auth::id(), 'season', (int) $id, 'activated', 'Saison als aktive Basis-Saison gesetzt');

        Flash::set('success', 'Saison aktiviert.');
        header('Location: ' . Url::to('/admin/seasons'));
        exit;
    }
}
