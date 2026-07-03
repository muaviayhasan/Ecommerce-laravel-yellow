<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A delivery/read receipt. `by` = who did it ('admin' | 'customer'); the OTHER
 * side flips its own outgoing ticks. `type` = 'delivered' (double tick) or
 * 'read' (blue tick). Broadcasts synchronously so ticks update instantly.
 */
class SupportReceipt implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $token,
        public int $conversationId,
        public string $by,
        public string $type,
    ) {}

    /** @return array<int, Channel|PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new Channel('support.conversation.' . $this->token),
            new PrivateChannel('support.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'receipt';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'by' => $this->by,
            'type' => $this->type,
        ];
    }
}
