<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\ChangeLog;

class ChangeLogController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        View::render('change_log/index', [
            'entries' => ChangeLog::recent(200),
        ]);
    }
}
