<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\SupportBot;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;

/**
 * Posts a friendly welcome message from the support team into a new customer's
 * support chat when their account is created — mirroring the automated order
 * lifecycle nudges. Best-effort: a chat hiccup must never break sign-up.
 */
class SendWelcomeSupportMessage
{
    public function __construct(private SupportBot $bot)
    {
    }

    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Customers only — never message staff accounts.
        if (! $user instanceof User || $user->isStaff()) {
            return;
        }

        $store = setting('general', 'app_name', config('app.name'));
        $firstName = Str::before($user->name, ' ') ?: $user->name;

        $body = "Hi {$firstName}! 👋 Welcome to {$store} — we're thrilled to have you with us. "
            . "This is the {$store} support team. If you have any questions about our products, "
            . "your orders or delivery, just reply here and we'll be glad to help. Happy shopping! 🛍️";

        try {
            $this->bot->notifyUser($user, $body);
        } catch (\Throwable $e) {
            report($e); // never let a support-chat error break registration
        }
    }
}
