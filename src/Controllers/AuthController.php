<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Url;
use App\Core\View;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: ' . Url::to('/'));
            exit;
        }
        View::render('auth/login', [], null);
    }

    public function login(): void
    {
        Csrf::requireValid();
        $email = trim((string) Request::input('email', ''));
        $password = (string) Request::input('password', '');

        if (Auth::attempt($email, $password)) {
            header('Location: ' . Url::to('/'));
            exit;
        }

        Flash::set('error', 'Anmeldung fehlgeschlagen. Bitte E-Mail und Passwort pruefen.');
        header('Location: ' . Url::to('/login'));
        exit;
    }

    public function logout(): void
    {
        Csrf::requireValid();
        Auth::logout();
        header('Location: ' . Url::to('/login'));
        exit;
    }
}
