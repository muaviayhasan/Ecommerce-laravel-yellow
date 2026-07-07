<?php

namespace App\Listeners;

use App\Mail\WelcomeMail;
use App\Models\User;
use App\Support\Mail\Notifier;
use Illuminate\Auth\Events\Registered;

/**
 * Sends the branded welcome email when a customer account is created. Gated by the
 * "registration_welcome" toggle (Settings → Email Notifications).
 */
class SendWelcomeEmail
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            Notifier::send('registration_welcome', $user->email, new WelcomeMail($user));
        }
    }
}
