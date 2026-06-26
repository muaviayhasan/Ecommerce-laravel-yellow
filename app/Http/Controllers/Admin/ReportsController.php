<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller implements HasMiddleware
{
    /** Orders in these states count as "returned" for the sales-vs-returns view. */
    private const RETURNED = ['cancelled', 'refunded'];

    public static function middleware(): array
    {
        return [
            new Middleware('can:reports.view', only: ['index']),
            new Middleware('can:reports.export', only: ['export']),
        ];
    }

    public function index(): View
    {
        $months = $this->months(12);
        $start = Carbon::createFromFormat('Y-m', $months->first()['key'])->startOfMonth();
        $labels = $months->pluck('label')->all();

        // One pass over the year's orders, grouped by calendar month.
        $byMonth = Order::where('created_at', '>=', $start)
            ->get(['created_at', 'grand_total', 'paid_total', 'status', 'payment_status'])
            ->groupBy(fn (Order $o) => $o->created_at->format('Y-m'));

        $revenueMonthly = $this->align($months, $byMonth, fn ($r) => $r->where('payment_status', 'paid')->sum('paid_total'));
        $ordersMonthly = $this->align($months, $byMonth, fn ($r) => $r->count());
        $salesMonthly = $this->align($months, $byMonth, fn ($r) => $r->whereNotIn('status', self::RETURNED)->sum('grand_total'));
        $returnsMonthly = $this->align($months, $byMonth, fn ($r) => $r->whereIn('status', self::RETURNED)->sum('grand_total'));

        // Profit = line total − cost, from order items joined to their orders.
        $itemsByMonth = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', $start)
            ->get(['orders.created_at as d', 'order_items.line_total', 'order_items.cost_snapshot', 'order_items.quantity'])
            ->groupBy(fn ($r) => Carbon::parse($r->d)->format('Y-m'));
        $profitMonthly = $months->map(fn ($m) => $itemsByMonth->has($m['key'])
            ? round($itemsByMonth[$m['key']]->sum(fn ($r) => (float) $r->line_total - ((float) $r->cost_snapshot * (float) $r->quantity)), 2)
            : 0.0)->all();

        $customersByMonth = Customer::where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn (Customer $c) => $c->created_at->format('Y-m'));
        $customersMonthly = $months->map(fn ($m) => $customersByMonth->has($m['key']) ? $customersByMonth[$m['key']]->count() : 0)->all();

        $lineMax = max(1.0, max($salesMonthly), max($returnsMonthly));

        $ordersPaid = Order::paid()->count();
        $revenueTotal = (float) Order::paid()->sum('paid_total');

        return view('admin.reports.index', [
            'labels' => $labels,
            'kpis' => [
                'revenue' => ['value' => $revenueTotal, 'money' => true, 'icon' => 'account_balance_wallet', 'tone' => 'primary', 'label' => 'Total revenue', 'trend' => $this->lastTrend($revenueMonthly), 'spark' => $revenueMonthly],
                'orders' => ['value' => (float) Order::count(), 'money' => false, 'icon' => 'receipt_long', 'tone' => 'tertiary', 'label' => 'Total orders', 'trend' => $this->lastTrend($ordersMonthly), 'spark' => $ordersMonthly],
                'customers' => ['value' => (float) Customer::count(), 'money' => false, 'icon' => 'group', 'tone' => 'secondary', 'label' => 'Total customers', 'trend' => $this->lastTrend(array_map('floatval', $customersMonthly)), 'spark' => $customersMonthly],
            ],
            'earnings' => [
                'revenue' => $revenueMonthly,
                'profit' => $profitMonthly,
                'max' => max(1.0, max($revenueMonthly), max(array_map(fn ($v) => max($v, 0), $profitMonthly))),
                'revenue_total' => array_sum($revenueMonthly),
                'profit_total' => array_sum($profitMonthly),
            ],
            'sales' => [
                'data' => $salesMonthly,
                'max' => max(1.0, max($salesMonthly)),
                'total' => array_sum($salesMonthly),
            ],
            'line' => [
                'salesPath' => $this->linePath($salesMonthly, $lineMax),
                'salesArea' => $this->linePath($salesMonthly, $lineMax, area: true),
                'returnsPath' => $this->linePath($returnsMonthly, $lineMax),
                'sales_total' => array_sum($salesMonthly),
                'returns_total' => array_sum($returnsMonthly),
                'return_rate' => array_sum($salesMonthly) + array_sum($returnsMonthly) > 0
                    ? round(array_sum($returnsMonthly) / (array_sum($salesMonthly) + array_sum($returnsMonthly)) * 100, 1)
                    : 0.0,
            ],
            'summary' => [
                'avg_order' => $ordersPaid > 0 ? $revenueTotal / $ordersPaid : 0.0,
                'profit_total' => array_sum($profitMonthly),
                'pending' => Order::whereIn('status', ['pending', 'processing'])->count(),
            ],
            'recentOrders' => Order::with('customer:id,name')->latest('id')->limit(8)->get(),
            'topProducts' => $this->topProducts(5),
        ]);
    }

    public function export(): StreamedResponse
    {
        $filename = 'orders-report-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order #', 'Date', 'Customer', 'Status', 'Payment', 'Total', 'Paid']);

            Order::with('customer:id,name')->latest('id')->chunk(200, function ($orders) use ($out) {
                foreach ($orders as $o) {
                    fputcsv($out, [
                        $o->order_number,
                        ($o->placed_at ?? $o->created_at)?->format('Y-m-d'),
                        $o->customer?->name ?? 'Guest',
                        $o->status,
                        $o->payment_status,
                        $o->grand_total,
                        $o->paid_total,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // Helpers ------------------------------------------------------------------

    /** @return Collection<int, array{key:string, label:string}> */
    private function months(int $n): Collection
    {
        return collect(range($n - 1, 0))->map(function (int $back) {
            $month = now()->startOfMonth()->subMonthsNoOverflow($back);

            return ['key' => $month->format('Y-m'), 'label' => $month->format('M')];
        });
    }

    /** Reduce a month-grouped collection into a value per month, aligned to $months. */
    private function align(Collection $months, Collection $grouped, callable $reduce): array
    {
        return $months->map(fn (array $m) => (float) ($grouped->has($m['key']) ? $reduce($grouped[$m['key']]) : 0))->all();
    }

    /** Month-over-month trend from the last two points of a monthly series. */
    private function lastTrend(array $series): float
    {
        $n = count($series);
        $current = $n > 0 ? (float) $series[$n - 1] : 0.0;
        $previous = $n > 1 ? (float) $series[$n - 2] : 0.0;

        if ($previous <= 0.0) {
            return $current > 0.0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /** SVG path across a 1000×180 box. With $area, closes the shape to the baseline for a fill. */
    private function linePath(array $values, float $max, int $w = 1000, int $h = 180, bool $area = false): string
    {
        $values = array_values($values);
        $n = count($values);
        if ($n === 0) {
            return '';
        }

        $max = max($max, 1);
        $step = $n > 1 ? $w / ($n - 1) : 0;
        $points = [];
        foreach ($values as $i => $v) {
            $x = round($i * $step, 1);
            $y = round($h - ((float) $v / $max) * ($h - 12), 1);
            $points[] = ($i === 0 ? 'M' : 'L') . $x . ',' . $y;
        }

        $path = implode(' ', $points);

        return $area ? $path . " L{$w},{$h} L0,{$h} Z" : $path;
    }

    /** @return Collection<int, object> best sellers by units */
    private function topProducts(int $limit): Collection
    {
        return OrderItem::query()
            ->selectRaw('products.id, products.name, SUM(order_items.quantity) as units, SUM(order_items.line_total) as revenue')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('units')
            ->limit($limit)
            ->get();
    }
}
