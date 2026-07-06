<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller implements HasMiddleware
{
    /** Fulfilment statuses an order moves through. */
    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded'];

    public static function middleware(): array
    {
        return [
            new Middleware('can:orders.view', only: ['index', 'show', 'print']),
            new Middleware('can:orders.edit', only: ['updateStatus', 'updateDelivery', 'recordPayment']),
        ];
    }

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with('customer:id,name')
            ->withCount('items')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q
                    ->where('order_number', 'like', $term)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('payment'), fn ($q) => $q->where('payment_status', $request->string('payment')))
            ->latest('id')
            ->paginate(per_page())
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => self::STATUSES,
            'stats' => [
                'total' => Order::count(),
                'to_fulfil' => Order::whereIn('status', ['pending', 'processing'])->count(),
                'revenue' => (float) Order::paid()->sum('paid_total'),
            ],
            'filters' => $request->only('search', 'status', 'payment'),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'customer', 'creator',
            'items.variant.product.media',
            'addresses', 'payments.receiver',
            'statusHistory.author',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => self::STATUSES,
        ]);
    }

    /** Printable bill — A4 invoice or 80mm thermal receipt (?format= overrides the store default). */
    public function print(Request $request, Order $order): View
    {
        $order->load(['customer', 'items.variant.product', 'addresses', 'payments']);

        return view('admin.orders.print', [
            'order' => $order,
            'billType' => bill_format($request->query('format')),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'note' => ['nullable', 'string', 'max:500'],
            'courier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ]);

        $from = $order->status;

        $order->fill([
            'status' => $data['status'],
            'courier' => $data['courier'] ?? $order->courier,
            'tracking_number' => $data['tracking_number'] ?? $order->tracking_number,
        ]);

        if ($data['status'] === 'delivered' && ! $order->delivered_at) {
            $order->delivered_at = now();
        }

        $order->save();

        // Record the transition (or a note-only update) in the order timeline.
        if ($from !== $data['status'] || filled($data['note'])) {
            $order->statusHistory()->create([
                'from_status' => $from,
                'to_status' => $data['status'],
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
            ]);
        }

        // Keep the customer posted when the status actually changes — support chat + email.
        if ($from !== $data['status']) {
            if ($order->user) {
                app(\App\Services\SupportBot::class)->notifyUser($order->user, $this->statusMessage($order, $data['status']));
            }

            $order->loadMissing('customer');
            $customerUrl = $order->user ? route('account.orders.show', $order) : null;
            \App\Support\Mail\Notifier::send(
                'order_status_update',
                $order->customer?->email,
                new \App\Mail\OrderStatusUpdatedMail($order, $this->statusEmailLine($order, $data['status']), $customerUrl),
            );
        }

        return back()->with('status', 'Order updated.');
    }

    /** Plain-text status line for the email body (no chat link / emoji). */
    private function statusEmailLine(Order $order, string $status): string
    {
        $num = $order->order_number;

        return match ($status) {
            'processing' => "Good news — your order {$num} is now being processed.",
            'shipped' => "Your order {$num} has been shipped and is on its way.",
            'delivered' => "Your order {$num} has been delivered. We hope you love it!",
            'completed' => "Your order {$num} is complete. Thank you for shopping with us!",
            'cancelled' => "Your order {$num} has been cancelled. Reply to this email if you have any questions.",
            'refunded' => "A refund has been processed for your order {$num}.",
            default => "Your order {$num} is now " . ucfirst(str_replace('_', ' ', $status)) . '.',
        };
    }

    /** Friendly support-chat message for an order status change. */
    private function statusMessage(Order $order, string $status): string
    {
        $num = $order->order_number;
        $body = match ($status) {
            'processing' => "🛠️ Good news! Your order {$num} is now being processed.",
            'shipped' => "🚚 Your order {$num} has been shipped" . ($order->tracking_number ? " (tracking: {$order->tracking_number})" : '') . '. It\'s on the way!',
            'delivered' => "✅ Your order {$num} has been delivered. Enjoy! Reply here if anything's not right.",
            'completed' => "🎉 Your order {$num} is complete. Thank you for shopping with us!",
            'cancelled' => "❌ Your order {$num} has been cancelled. Reply here if you have any questions.",
            'refunded' => "💸 A refund has been processed for your order {$num}.",
            default => "📦 Update: your order {$num} is now " . ucfirst(str_replace('_', ' ', $status)) . '.',
        };

        return $body . "\nView it: " . route('account.orders.show', $order);
    }

    /** Record a customer payment (COD collected / bank transfer received) against the order. */
    public function recordPayment(Request $request, Order $order, \App\Services\SalesService $sales): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(['cash', 'bank'])],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $sales->recordPayment($order, $data);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Payment recorded.');
    }

    /** Update delivery details (method / handler / contact). No financial change. */
    public function updateDelivery(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'shipping_method' => ['nullable', Rule::in(['pickup', 'own_rider', 'courier', 'other'])],
            'courier' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
        ]);

        $order->update([
            'shipping_method' => $data['shipping_method'] ?? null,
            'courier' => $data['courier'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
        ]);

        return back()->with('status', 'Delivery details updated.');
    }
}
