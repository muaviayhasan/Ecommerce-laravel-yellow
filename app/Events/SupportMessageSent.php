<?php

namespace App\Events;

use App\Models\SupportMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A support message was created. Broadcast on the conversation's public channel
 * (so the customer widget updates) and the staff firehose (so the admin inbox
 * updates + rings). Broadcasts synchronously — Reverb only, no queue worker.
 */
class SupportMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SupportMessage $message, public string $token) {}

    /** @return array<int, Channel|PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new Channel('support.conversation.' . $this->token), // public: guest-friendly, unguessable token
            new PrivateChannel('support.admin'),                 // staff only
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->support_conversation_id,
            'body' => $this->message->body,
            'from_admin' => (bool) $this->message->from_admin,
            'at' => $this->message->created_at?->format('H:i'),
            'status' => $this->message->read_at ? 'read' : ($this->message->delivered_at ? 'delivered' : 'sent'),
        ];
    }
}
