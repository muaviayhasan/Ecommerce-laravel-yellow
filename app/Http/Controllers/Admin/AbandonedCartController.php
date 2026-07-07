<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Read-only insight into abandoned-cart recovery: how many carts are open, their
 * potential value, and how much has been won back. Rows are created by the
 * storefront (checkout capture) and closed by the reminder flow — staff only view
 * and prune here.
 */
class AbandonedCartController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:abandoned-carts.view', only: ['index']),
            new Middleware('can:abandoned-carts.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $carts = AbandonedCart::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('email', 'like', $term)->orWhere('name', 'like', $term));
            })
            ->when($request->input('status') === 'open', fn ($q) => $q->whereNull('recovered_at'))
            ->when($request->input('status') === 'recovered', fn ($q) => $q->whereNotNull('recovered_at'))
            ->when($request->input('status') === 'reminded', fn ($q) => $q->whereNull('recovered_at')->where('reminders_sent', '>', 0));

        $this->applyTableSort($carts, $request, [
            'email' => 'email',
            'value' => 'subtotal',
            'items' => 'item_count',
            'reminders' => 'reminders_sent',
            'reminded' => 'last_reminded_at',
            'status' => 'recovered_at',
            'created' => 'created_at',
        ], fn ($q) => $q->latest('id'));

        $perPage = $this->perPageFor($request);

        return view('admin.abandoned-carts.index', [
            'carts' => $carts->paginate($perPage)->withQueryString(),
            'filters' => $request->only('search', 'status', 'sort', 'dir', 'per_page'),
            'perPage' => $perPage,
            'stats' => $this->stats(),
        ]);
    }

    public function destroy(AbandonedCart $abandonedCart): RedirectResponse
    {
        $abandonedCart->delete();

        return back()->with('status', 'Abandoned cart removed.');
    }

    /**
     * Headline recovery figures. "Recovery rate" is recovered carts over every
     * cart that has ever been abandoned.
     *
     * @return array<string, int|float>
     */
    private function stats(): array
    {
        $open = AbandonedCart::whereNull('recovered_at')->count();
        $recovered = AbandonedCart::whereNotNull('recovered_at')->count();
        $total = $open + $recovered;

        return [
            'open' => $open,
            'open_value' => (float) AbandonedCart::whereNull('recovered_at')->sum('subtotal'),
            'recovered' => $recovered,
            'recovered_value' => (float) AbandonedCart::whereNotNull('recovered_at')->sum('subtotal'),
            'rate' => $total > 0 ? round($recovered / $total * 100) : 0,
        ];
    }
}
