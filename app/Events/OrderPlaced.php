<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new storefront order was placed. Broadcast on the staff channel so the admin
 * header notification bell updates + rings in realtime. Broadcasts synchronously
 * (Reverb only, no queue worker) and is best-effort — never fails the checkout.
 */
class OrderPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.orders')];
    }

    public function broadcastAs(): string
    {
        return 'order.placed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'number' => $this->order->order_number,
            'total' => format_money($this->order->grand_total),
            'customer' => $this->order->customer?->name ?? 'Customer',
            'url' => route('admin.orders.show', $this->order),
            'at' => $this->order->created_at?->format('M j, H:i'),
        ];
    }
}
