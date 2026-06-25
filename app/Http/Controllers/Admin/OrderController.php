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
            new Middleware('can:orders.edit', only: ['updateStatus']),
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

    /** Printable bill — A4 invoice or 80mm thermal receipt, per the store setting. */
    public function print(Order $order): View
    {
        $order->load(['customer', 'items.variant.product', 'addresses', 'payments']);

        return view('admin.orders.print', [
            'order' => $order,
            'billType' => setting('store', 'bill_type', 'a4') === 'thermal' ? 'thermal' : 'a4',
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

        return back()->with('status', 'Order updated.');
    }
}
