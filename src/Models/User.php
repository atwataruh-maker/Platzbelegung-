<?php

namespace App\Models;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        $stmt = static::db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function trainers(): array
    {
        return static::db()->query(
            "SELECT * FROM users WHERE role IN ('trainer','betreuer') AND active = 1 ORDER BY name"
        )->fetchAll();
    }

    public static function admins(): array
    {
        return static::db()->query(
            "SELECT * FROM users WHERE role = 'admin' AND active = 1 ORDER BY name"
        )->fetchAll();
    }

    public static function createWithPassword(array $data, string $plainPassword): int
    {
        $data['password_hash'] = password_hash($plainPassword, PASSWORD_DEFAULT);
        return static::create($data);
    }

    public static function setPassword(int $id, string $plainPassword): bool
    {
        return static::update($id, ['password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT)]);
    }
}
