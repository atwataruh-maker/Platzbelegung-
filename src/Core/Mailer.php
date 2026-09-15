<?php

namespace App\Core;

/**
 * Duenner Wrapper um PHP mail(). Bewusst ohne PHPMailer/Composer-Abhaengigkeit,
 * damit die Anwendung ohne Build-Schritt auf STRATO-Shared-Hosting laeuft.
 * STRATO-Postfachversand funktioniert ueber die normale mail()-Funktion,
 * solange Absenderadresse und Domain zusammenpassen (SPF).
 */
class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $body): bool
    {
        if (!Config::get('mail.enabled', true)) {
            return false;
        }

        $fromAddress = Config::get('mail.from_address');
        $fromName = Config::get('mail.from_name');

        $headers = [];
        $headers[] = 'From: ' . self::encodeHeader($fromName) . ' <' . $fromAddress . '>';
        $headers[] = 'Reply-To: ' . $fromAddress;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';

        $to = self::encodeHeader($toName) . ' <' . $toEmail . '>';
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        return @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
