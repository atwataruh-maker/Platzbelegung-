<?php
// Kleines CLI-Hilfsskript: erzeugt einen bcrypt-Hash fuer ein Passwort.
// Aufruf: php database/generate_password_hash.php "MeinNeuesPasswort"

if ($argc < 2) {
    fwrite(STDERR, "Aufruf: php generate_password_hash.php \"Passwort\"\n");
    exit(1);
}

echo password_hash($argv[1], PASSWORD_DEFAULT), PHP_EOL;
