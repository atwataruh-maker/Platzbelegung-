<?php

namespace App\Core;

/**
 * Einfache symmetrische Verschluesselung fuer sensible Werte in der
 * Datenbank (aktuell: DFBnet-Passwort). Schuetzt gegen zufaelliges
 * Mitlesen bei einem Datenbank-Dump/-Leak; der Schluessel selbst liegt
 * wie die DB-Zugangsdaten nur in config/config.php (nicht in git).
 */
class Crypto
{
    private const CIPHER = 'aes-256-cbc';

    public static function encrypt(string $plaintext): string
    {
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $cipherText = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
        if ($cipherText === false) {
            throw new \RuntimeException('Verschluesselung fehlgeschlagen.');
        }
        return base64_encode($iv . $cipherText);
    }

    public static function decrypt(string $encoded): ?string
    {
        $raw = base64_decode($encoded, true);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($raw === false || strlen($raw) <= $ivLength) {
            return null;
        }
        $iv = substr($raw, 0, $ivLength);
        $cipherText = substr($raw, $ivLength);
        $plain = openssl_decrypt($cipherText, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
        return $plain === false ? null : $plain;
    }

    private static function key(): string
    {
        // sha256 liefert unabhaengig von der Laenge des konfigurierten
        // Schluessels immer die von aes-256-cbc benoetigten 32 Byte.
        return hash('sha256', (string) Config::get('security.encryption_key', ''), true);
    }
}
