<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:dashboard.view')];
    }

    public function index(): View
    {
        $now = now();
        $startThisMonth = $now->copy()->startOfMonth();
        $startLastMonth = $now->copy()->subMonthNoOverflow()->startOfMonth();

        // --- Stat cards (value + month-over-month trend) -----------------------
        $stats = [
            'sales' => [
                'value' => Order::count(),
                'trend' => $this->trend(
                    Order::where('created_at', '>=', $startThisMonth)->count(),
                    Order::whereBetween('created_at', [$startLastMonth, $startThisMonth])->count(),
                ),
            ],
            'income' => [
                'value' => (float) Order::paid()->sum('paid_total'),
                'trend' => $this->trend(
                    (float) Order::paid()->where('created_at', '>=', $startThisMonth)->sum('paid_total'),
                    (float) Order::paid()->whereBetween('created_at', [$startLastMonth, $startThisMonth])->sum('paid_total'),
                ),
            ],
            'paid_orders' => [
                'value' => Order::paid()->count(),
                'trend' => $this->trend(
                    Order::paid()->where('created_at', '>=', $startThisMonth)->count(),
                    Order::paid()->whereBetween('created_at', [$startLastMonth, $startThisMonth])->count(),
                ),
            ],
            'customers' => [
                'value' => Customer::count(),
                'trend' => $this->trend(
                    Customer::where('created_at', '>=', $startThisMonth)->count(),
                    Customer::whereBetween('created_at', [$startLastMonth, $startThisMonth])->count(),
                ),
            ],
        ];

        // --- Recent Order bar chart (orders per month, last 12 months) ---------
        $orderChart = $this->ordersPerMonth(12);

        // --- Earnings chart (revenue + profit per month, last 6 months) --------
        $earnings = $this->earningsPerMonth(6);

        // --- Top products (by units sold) --------------------------------------
        $topProducts = $this->topProducts(4);

        // --- Top customers (by spend) ------------------------------------------
        $topCustomers = Customer::query()
            ->withCount('orders')
            ->withSum('orders as total_spent', 'grand_total')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // --- Recent products overview ------------------------------------------
        $recentProducts = Product::with('defaultVariant')
            ->latest()
            ->limit(5)
            ->get();

        // --- Recent reviews ----------------------------------------------------
        $reviews = Review::with('user:id,name,avatar')
            ->latest()
            ->limit(3)
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'orderChart', 'earnings', 'topProducts', 'topCustomers', 'recentProducts', 'reviews',
        ));
    }

    /** Percentage change of $current vs $previous (0 when there's no baseline). */
    private function trend(float $current, float $previous): float
    {
        if ($previous <= 0.0) {
            return $current > 0.0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Build a labelled list of the last $n calendar months (oldest → newest).
     *
     * @return \Illuminate\Support\Collection<int, array{key:string, label:string}>
     */
    private function months(int $n): \Illuminate\Support\Collection
    {
        return collect(range($n - 1, 0))->map(function (int $back) {
            $month = now()->startOfMonth()->subMonthsNoOverflow($back);

            return ['key' => $month->format('Y-m'), 'label' => $month->format('M')];
        });
    }

    /**
     * Orders counted per month. Returns labels + counts + the peak (for bar scaling).
     */
    private function ordersPerMonth(int $n): array
    {
        $months = $this->months($n);
        $start = Carbon::createFromFormat('Y-m', $months->first()['key'])->startOfMonth();

        $counts = Order::where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn (Order $o) => $o->created_at->format('Y-m'))
            ->map->count();

        $data = $months->map(fn (array $m) => [
            'label' => $m['label'],
            'count' => (int) ($counts[$m['key']] ?? 0),
        ]);

        return [
            'data' => $data,
            'max' => max(1, (int) $data->max('count')),
        ];
    }

    /**
     * Revenue (paid order totals) and profit (line total − cost) per month.
     */
    private function earningsPerMonth(int $n): array
    {
        $months = $this->months($n);
        $start = Carbon::createFromFormat('Y-m', $months->first()['key'])->startOfMonth();

        $revByMonth = Order::paid()
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'paid_total'])
            ->groupBy(fn (Order $o) => $o->created_at->format('Y-m'))
            ->map(fn ($rows) => (float) $rows->sum('paid_total'));

        $profitByMonth = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.created_at', '>=', $start)
            ->get(['orders.created_at as order_date', 'order_items.line_total', 'order_items.cost_snapshot', 'order_items.quantity'])
            ->groupBy(fn ($row) => Carbon::parse($row->order_date)->format('Y-m'))
            ->map(fn ($rows) => (float) $rows->sum(fn ($r) => (float) $r->line_total - ((float) $r->cost_snapshot * (float) $r->quantity)));

        $data = $months->map(fn (array $m) => [
            'label' => $m['label'],
            'revenue' => (float) ($revByMonth[$m['key']] ?? 0),
            'profit' => (float) ($profitByMonth[$m['key']] ?? 0),
        ]);

        return [
            'data' => $data,
            'max' => max(1.0, (float) $data->max('revenue')),
            'revenue_total' => (float) $data->sum('revenue'),
            'profit_total' => (float) $data->sum('profit'),
        ];
    }

    /**
     * Best-selling products by units sold, with their primary image.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function topProducts(int $limit): \Illuminate\Support\Collection
    {
        $rows = OrderItem::query()
            ->selectRaw('products.id, products.name, products.slug, SUM(order_items.quantity) as units')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->groupBy('products.id', 'products.name', 'products.slug')
            ->orderByDesc('units')
            ->limit($limit)
            ->get();

        $images = Product::with('media:id,disk,path')
            ->whereIn('id', $rows->pluck('id'))
            ->get()
            ->keyBy('id');

        return $rows->map(fn ($row) => (object) [
            'name' => $row->name,
            'units' => (int) $row->units,
            'image' => $images->get($row->id)?->media->first()?->url,
        ]);
    }
}
