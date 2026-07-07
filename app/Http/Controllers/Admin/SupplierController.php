<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupplierRequest;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:suppliers.view', only: ['index']),
            new Middleware('can:suppliers.create', only: ['create', 'store']),
            new Middleware('can:suppliers.edit', only: ['edit', 'update']),
            new Middleware('can:suppliers.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->withCount('purchases')
            // Outstanding payable from received purchases (opening_balance added in the view).
            ->withSum(['purchases as received_grand' => fn ($q) => $q->where('status', 'received')], 'grand_total')
            ->withSum(['purchases as received_paid' => fn ($q) => $q->where('status', 'received')], 'paid_total')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('company', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'));

        $this->applyTableSort($suppliers, $request, [
            'name' => 'name',
            'purchases' => 'purchases_count',
            'status' => 'is_active',
        ], fn ($q) => $q->orderBy('name'));

        $perPage = $this->perPageFor($request);
        $suppliers = $suppliers->paginate($perPage)->withQueryString();

        $outstanding = (float) Purchase::where('status', 'received')->sum(DB::raw('grand_total - paid_total'))
            + (float) Supplier::sum('opening_balance');

        return view('admin.suppliers.index', [
            'suppliers' => $suppliers,
            'perPage' => $perPage,
            'stats' => [
                'total' => Supplier::count(),
                'active' => Supplier::where('is_active', true)->count(),
                'payable' => $outstanding,
            ],
            'filters' => $request->only('search', 'status', 'sort', 'dir', 'per_page'),
        ]);
    }

    public function create(): View
    {
        return view('admin.suppliers.create', [
            'supplier' => new Supplier(['is_active' => true, 'opening_balance' => 0]),
        ]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier created.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.edit', ['supplier' => $supplier]);
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists()) {
            return back()->with('error', 'Cannot delete a supplier that has purchases. Deactivate it instead.');
        }

        $supplier->delete();

        return redirect()->route('admin.suppliers.index')->with('status', 'Supplier deleted.');
    }
}
