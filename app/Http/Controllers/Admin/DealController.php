<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DealRequest;
use App\Models\Deal;
use App\Models\Media;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Deals — promotions bundling variants across products (admin-managed; §4.1 RBAC). */
class DealController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:deals.view', only: ['index']),
            new Middleware('can:deals.create', only: ['create', 'store']),
            new Middleware('can:deals.edit', only: ['edit', 'update', 'searchVariants']),
            new Middleware('can:deals.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $deals = Deal::query()
            ->with(['image', 'items.variant.product:id,name'])
            ->withCount('items')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->string('search') . '%'))
            ->orderBy('sort_order')->latest('id')
            ->paginate(per_page())
            ->withQueryString();

        return view('admin.deals.index', [
            'deals' => $deals,
            'stats' => [
                'total' => Deal::count(),
                'live' => Deal::live()->count(),
                'scheduled' => Deal::active()->whereNotNull('starts_at')->where('starts_at', '>', now())->count(),
                'expired' => Deal::whereNotNull('ends_at')->where('ends_at', '<', now())->count(),
            ],
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): View
    {
        return view('admin.deals.create', [
            'deal' => new Deal(['type' => Deal::TYPE_SALE, 'is_active' => true, 'discount_type' => 'fixed', 'discount_value' => 0]),
            'itemsState' => [],
            'mediaItems' => $this->mediaItems(),
        ]);
    }

    public function store(DealRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $deal = DB::transaction(function () use ($data) {
            $deal = Deal::create(Arr::except($data, ['items']));
            $this->syncItems($deal, $data['items']);

            return $deal;
        });

        return redirect()->route('admin.deals.edit', $deal)->with('status', 'Deal created.');
    }

    public function edit(Deal $deal): View
    {
        $deal->load('items.variant.product:id,name', 'items.variant.image', 'items.variant.product.media');

        return view('admin.deals.edit', [
            'deal' => $deal,
            'itemsState' => $deal->items->map(fn ($item) => [
                'variant_id' => $item->product_variant_id,
                'name' => $item->variant?->product?->name ?? 'Item',
                'sku' => $item->variant?->sku ?? '—',
                'price' => (float) ($item->variant?->retail_price ?? 0),
                'image' => $this->variantImage($item->variant),
                'quantity' => (float) $item->quantity,
            ])->values()->all(),
            'mediaItems' => $this->mediaItems(),
        ]);
    }

    public function update(DealRequest $request, Deal $deal): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($deal, $data) {
            $deal->update(Arr::except($data, ['items']));
            $this->syncItems($deal, $data['items']);
        });

        return redirect()->route('admin.deals.edit', $deal)->with('status', 'Deal updated.');
    }

    public function destroy(Deal $deal): RedirectResponse
    {
        $deal->delete();

        return redirect()->route('admin.deals.index')->with('status', 'Deal deleted.');
    }

    /** JSON variant search for the items builder (same payload shape as the POS picker). */
    public function searchVariants(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));
        $offset = max(0, $request->integer('offset'));

        $query = ProductVariant::query()
            ->where('product_variants.is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->with(['product:id,name', 'product.media', 'image']);

        if ($term !== '') {
            $like = '%' . $term . '%';
            $query->where(fn ($q) => $q
                ->where('sku', 'like', $like)
                ->orWhere('barcode', $term)
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like)));
        }

        $variants = $query->orderByDesc('id')
            ->skip($offset)->take(15)
            ->get(['id', 'product_id', 'sku', 'retail_price', 'image_media_id']);

        return response()->json($variants->map(fn (ProductVariant $v) => [
            'id' => $v->id,
            'name' => $v->product?->name ?? 'Item',
            'sku' => $v->sku,
            'price' => (float) $v->retail_price,
            'image' => $this->variantImage($v),
        ]));
    }

    /** Replace the deal's items with the submitted set (keyed by variant). */
    private function syncItems(Deal $deal, array $items): void
    {
        $deal->items()->whereNotIn('product_variant_id', collect($items)->pluck('variant_id'))->delete();

        foreach (array_values($items) as $i => $row) {
            $deal->items()->updateOrCreate(
                ['product_variant_id' => (int) $row['variant_id']],
                ['quantity' => (float) $row['quantity'], 'sort_order' => $i],
            );
        }
    }

    /** Thumbnail for a variant: its own image, else the product's primary image. */
    private function variantImage(?ProductVariant $variant): ?string
    {
        if (! $variant) {
            return null;
        }
        if ($variant->image) {
            return $variant->image->url;
        }

        $media = $variant->product?->media;

        return ($media?->firstWhere('pivot.is_primary', true) ?? $media?->first())?->url;
    }

    /** Gallery items for the deal image picker (the picker lazy-loads the rest). */
    private function mediaItems()
    {
        return Media::query()->latest('id')->take(24)->get(['id', 'disk', 'path', 'title']);
    }
}
