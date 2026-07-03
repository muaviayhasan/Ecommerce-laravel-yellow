<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Staff blocked (or unblocked) a conversation. Pushed to the customer's widget so
 * it disables/enables the composer instantly. Broadcasts synchronously.
 */
class SupportBlocked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public string $token, public int $conversationId, public bool $blocked) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new Channel('support.conversation.' . $this->token)];
    }

    public function broadcastAs(): string
    {
        return 'blocked';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return ['conversation_id' => $this->conversationId, 'blocked' => $this->blocked];
    }
}
