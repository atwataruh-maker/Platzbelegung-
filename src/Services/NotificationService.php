<?php

namespace App\Services;

use App\Core\Mailer;
use App\Models\Conflict;
use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /** Versendet alle wartenden Benachrichtigungen per E-Mail (Abschnitt 10 + 12). */
    public static function flushPending(int $limit = 200): int
    {
        $sent = 0;
        foreach (Notification::pending($limit) as $notification) {
            $ok = Mailer::send($notification['email'], $notification['user_name'], $notification['subject'], $notification['body']);
            if ($ok) {
                Notification::markSent((int) $notification['id']);
                $sent++;
            } else {
                Notification::markFailed((int) $notification['id']);
            }
        }
        return $sent;
    }

    /** Woechentliche Konfliktuebersicht an alle Admins (Abschnitt 12). */
    public static function sendWeeklyAdminDigest(): void
    {
        $open = Conflict::allOpen();
        if ($open === []) {
            return;
        }

        $lines = ["Offene Konflikte fuer die kommende Woche:", ""];
        foreach ($open as $conflict) {
            $lines[] = sprintf(
                '[%s] %s (%s) - %s bis %s auf %s',
                strtoupper($conflict['severity']),
                $conflict['conflict_type'],
                $conflict['occurrence_date'],
                $conflict['start_datetime'],
                $conflict['end_datetime'],
                $conflict['pitch_name'] ?? 'unbekannt'
            );
        }
        $body = implode("\n", $lines);

        foreach (User::admins() as $admin) {
            Notification::create([
                'user_id' => $admin['id'],
                'subject' => 'Woechentliche Konfliktuebersicht Platzbelegung',
                'body' => $body,
                'related_type' => 'weekly_digest',
            ]);
        }
    }
}
