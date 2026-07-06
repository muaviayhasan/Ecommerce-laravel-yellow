<?php

namespace App\Support\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Single gate for every transactional / admin email. Each email type maps to a
 * toggle key in the "emails" settings group (Admin → Settings → Email
 * Notifications); when the admin switches a type off, Notifier::send() silently
 * skips it. Campaign (marketing) sends are explicit and do NOT route through here.
 */
class Notifier
{
    /**
     * Queue $mailable to $to only if the "emails.$toggleKey" setting is on.
     * Returns true when queued, false when suppressed by the toggle or when
     * there is no valid recipient.
     *
     * @param  string|array<int, string>|null  $to
     */
    public static function send(string $toggleKey, string|array|null $to, Mailable $mailable): bool
    {
        if (! self::enabled($toggleKey)) {
            return false;
        }

        $recipients = array_values(array_filter((array) $to, fn ($e) => filled($e)));

        if (! $recipients) {
            return false;
        }

        Mail::to($recipients)->queue($mailable);

        return true;
    }

    /** Whether a given email type is enabled (defaults to on when unset). */
    public static function enabled(string $toggleKey): bool
    {
        return (bool) setting('emails', $toggleKey, true);
    }
}
