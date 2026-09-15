<?php

namespace App\Models;

use App\Core\Crypto;

/**
 * Genau eine Zeile (id=1): die DFBnet-Zugangsdaten, die der Admin unter
 * /admin/dfbnet hinterlegt und die die DFBnet-Import-Automatisierung
 * (automation/dfbnet-import) ueber /import/dfbnet-credentials abruft.
 */
class DfbnetCredential extends Model
{
    protected static string $table = 'dfbnet_credentials';

    public static function get(): ?array
    {
        $row = static::db()->query('SELECT * FROM dfbnet_credentials WHERE id = 1')->fetch();
        return $row === false ? null : $row;
    }

    public static function save(string $username, string $password, ?int $updatedBy): void
    {
        $stmt = static::db()->prepare(
            'INSERT INTO dfbnet_credentials (id, username, password_encrypted, updated_by, updated_at)
             VALUES (1, :username, :password_encrypted, :updated_by, NOW())
             ON DUPLICATE KEY UPDATE
                username = VALUES(username),
                password_encrypted = VALUES(password_encrypted),
                updated_by = VALUES(updated_by),
                updated_at = NOW()'
        );
        $stmt->execute([
            'username' => $username,
            'password_encrypted' => Crypto::encrypt($password),
            'updated_by' => $updatedBy,
        ]);
    }

    public static function decryptedPassword(): ?string
    {
        $row = self::get();
        return $row === null ? null : Crypto::decrypt($row['password_encrypted']);
    }
}
