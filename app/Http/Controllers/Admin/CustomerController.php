<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CustomerController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:customers.view', only: ['index']),
            new Middleware('can:customers.create', only: ['create', 'store']),
            new Middleware('can:customers.edit', only: ['edit', 'update']),
            new Middleware('can:customers.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->withCount('orders')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'));

        $this->applyTableSort($customers, $request, [
            'name' => 'name',
            'phone' => 'phone',
            'type' => 'type',
            'orders' => 'orders_count',
            'balance' => 'opening_balance',
            'status' => 'is_active',
        ], fn ($q) => $q->latest('id'));

        $perPage = $this->perPageFor($request);
        $customers = $customers->paginate($perPage)->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'perPage' => $perPage,
            'stats' => [
                'total' => Customer::count(),
                'active' => Customer::where('is_active', true)->count(),
                'wholesale' => Customer::where('type', 'wholesale')->count(),
            ],
            'filters' => $request->only('search', 'type', 'status', 'sort', 'dir', 'per_page'),
        ]);
    }

    public function create(): View
    {
        return view('admin.customers.create', [
            'customer' => new Customer(['type' => 'retail', 'price_tier' => 'retail', 'is_active' => true, 'opening_balance' => 0]),
        ]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return redirect()->route('admin.customers.index')->with('status', 'Customer created.');
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.edit', ['customer' => $customer]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('admin.customers.index')->with('status', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->orders()->exists()) {
            return back()->with('error', 'Cannot delete a customer with orders. Deactivate them instead.');
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')->with('status', 'Customer deleted.');
    }
}
