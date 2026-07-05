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

        return back()->with('status', 'Order updated.');
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
