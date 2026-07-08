<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CouponController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:coupons.view', only: ['index']),
            new Middleware('can:coupons.create', only: ['create', 'store']),
            new Middleware('can:coupons.edit', only: ['edit', 'update']),
            new Middleware('can:coupons.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $coupons = Coupon::query()
            ->withCount('orders')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('code', 'like', $term)->orWhere('description', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'));

        $this->applyTableSort($coupons, $request, [
            'code' => 'code',
            'min' => 'min_subtotal',
            'usage' => 'used_count',
            'validity' => 'expires_at',
            'status' => 'is_active',
        ], fn ($q) => $q->latest('id'));

        $perPage = $this->perPageFor($request);
        $coupons = $coupons->paginate($perPage)->withQueryString();

        return view('admin.coupons.index', [
            'coupons' => $coupons,
            'filters' => $request->only('search', 'status', 'sort', 'dir', 'per_page'),
            'perPage' => $perPage,
            'stats' => [
                'total' => Coupon::count(),
                'active' => Coupon::where('is_active', true)->count(),
                'expired' => Coupon::whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
                'redemptions' => (int) Coupon::sum('used_count'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.coupons.create', [
            'coupon' => new Coupon(['type' => 'percent', 'is_active' => true]),
            'customers' => $this->customerOptions(),
        ]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        $coupon = Coupon::create($request->safe()->except('customer_ids'));
        $coupon->customers()->sync($request->input('customer_ids', []));

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.edit', [
            'coupon' => $coupon->load('customers:id'),
            'customers' => $this->customerOptions(),
        ]);
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($request->safe()->except('customer_ids'));
        $coupon->customers()->sync($request->input('customer_ids', []));

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon updated.');
    }

    /** Customers available to target a coupon at (searchable multi-select). */
    private function customerOptions()
    {
        return Customer::orderBy('name')->get(['id', 'name', 'email']);
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        if ($coupon->orders()->exists()) {
            return back()->with('error', 'This coupon has been used on orders — deactivate it instead of deleting.');
        }

        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon deleted.');
    }
}
