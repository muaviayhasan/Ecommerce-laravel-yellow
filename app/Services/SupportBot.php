<?php

namespace App\Services;

use App\Events\SupportMessageSent;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;

/**
 * Automated support messages. Posts a system message (rendered as an incoming
 * support reply, no human agent) into a customer's chat and broadcasts it so the
 * floating widget updates + rings in realtime. Used for order lifecycle nudges.
 */
class SupportBot
{
    /** Send a system message to a signed-in customer's conversation (created on demand). */
    public function notifyUser(User $user, string $body): SupportMessage
    {
        return $this->say($this->conversationFor($user), $body);
    }

    /** Post a system message and best-effort broadcast it. */
    public function say(SupportConversation $conversation, string $body): SupportMessage
    {
        $message = $conversation->messages()->create([
            'from_admin' => true,
            'user_id' => null, // system — not a specific agent
            'body' => $body,
        ]);

        $conversation->update(['last_message_at' => now(), 'status' => 'open']);

        try {
            broadcast(new SupportMessageSent($message, $conversation->channelToken()));
        } catch (\Throwable $e) {
            report($e); // never let a downed socket server break the request
        }

        return $message;
    }

    /** The customer's latest conversation, created if they've never chatted before. */
    public function conversationFor(User $user): SupportConversation
    {
        return SupportConversation::where('user_id', $user->id)->latest('id')->first()
            ?? SupportConversation::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => 'open',
            ]);
    }
}
