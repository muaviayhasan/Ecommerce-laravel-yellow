<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Services\ProductWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProductController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ProductWriter $writer)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('can:products.view', only: ['index', 'show']),
            new Middleware('can:products.create', only: ['create', 'store', 'duplicate']),
            new Middleware('can:products.edit', only: ['edit', 'update']),
            new Middleware('can:products.delete', only: ['destroy']),
        ];
    }

    /**
     * Clone a product into a hidden draft: everything copies (variants with
     * zero stock, attributes, images, specs) except storefront exposure —
     * the copy is unlisted, unpublished and carries no curation flags.
     */
    public function duplicate(Product $product): RedirectResponse
    {
        $product->load(['variants.attributeValues', 'attributes', 'media']);

        $copy = \Illuminate\Support\Facades\DB::transaction(function () use ($product) {
            $new = $product->replicate(['slug', 'sku']);
            $new->name = $product->name . ' (Copy)';
            $new->slug = $this->writer->uniqueSlug($product->slug . '-copy');
            $new->sku = $this->writer->uniqueSku($product->sku . '-COPY');
            $new->is_web_listed = false;   // live-on-store off
            $new->published_at = null;     // draft
            $new->is_featured = false;
            $new->is_trending = false;
            $new->is_bestseller = false;
            $new->is_pinned = false;
            $new->save();

            $new->attributes()->sync($product->attributes->pluck('id')->all());

            $mediaSync = [];
            foreach ($product->media as $m) {
                $mediaSync[$m->id] = ['sort_order' => (int) $m->pivot->sort_order, 'is_primary' => (bool) $m->pivot->is_primary];
            }
            $new->media()->sync($mediaSync);

            foreach ($product->variants as $variant) {
                $nv = $variant->replicate(['sku']);
                $nv->product_id = $new->id;
                $nv->sku = $this->writer->uniqueSku($variant->sku . '-COPY');
                $nv->stock_quantity = 0;      // stock never duplicates — receive it via purchases
                $nv->reserved_quantity = 0;
                $nv->save();
                $nv->attributeValues()->sync($variant->attributeValues->pluck('id')->all());
            }

            return $new;
        });

        return redirect()->route('admin.products.edit', $copy)
            ->with('status', "Duplicated as “{$copy->name}” — hidden from the store and unpublished. Review it, then publish when ready.");
    }

    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'defaultVariant.image', 'media:id,disk,path'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('slug', 'like', $term));
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->when($request->filled('brand'), fn ($q) => $q->where('brand_id', $request->integer('brand')))
            ->when($request->filled('status'), function ($q) use ($request) {
                match ((string) $request->string('status')) {
                    'active' => $q->where('is_active', true),
                    'inactive' => $q->where('is_active', false),
                    'web' => $q->webListed(),
                    default => null,
                };
            });

        $this->applySort($products, (string) $request->string('sort'), (string) $request->string('dir'));

        $perPage = $this->perPageFor($request);
        $products = $products->paginate($perPage)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'brands' => Brand::orderBy('name')->pluck('name', 'id'),
            'perPage' => $perPage,
            'stats' => [
                'total' => Product::count(),
                'active' => Product::where('is_active', true)->count(),
                'web_listed' => Product::webListed()->count(),
                'featured' => Product::where('is_featured', true)->count(),
            ],
            'filters' => $request->only('search', 'category', 'brand', 'status', 'sort', 'dir', 'per_page'),
        ]);
    }

    /** Apply an allow-listed sort to the product list (price/stock read the default variant). */
    private function applySort(\Illuminate\Database\Eloquent\Builder $query, string $sort, string $dir): void
    {
        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';

        // Price/stock live on the default variant → order by an aliased sub-select
        // (reliable in both directions, unlike a subquery passed straight to orderBy).
        $variantCol = fn (string $col) => ProductVariant::select($col)
            ->whereColumn('product_variants.product_id', 'products.id')
            ->where('is_default', true)
            ->limit(1);

        match ($sort) {
            'name' => $query->orderBy('name', $dir),
            'sku' => $query->orderBy('sku', $dir),
            'price' => $query->select('products.*')->selectSub($variantCol('retail_price'), 'sort_value')->orderBy('sort_value', $dir),
            'stock' => $query->select('products.*')->selectSub($variantCol('stock_quantity'), 'sort_value')->orderBy('sort_value', $dir),
            'status' => $query->orderBy('is_active', $dir)->orderByDesc('id'),
            default => $query->latest('id'), // newest first
        };
    }

    /** Page size: an allow-listed ?per_page override, else the store default. */
    private function perPageFor(Request $request): int
    {
        $pp = $request->integer('per_page');

        return in_array($pp, [15, 25, 50, 100], true) ? $pp : per_page();
    }

    public function show(Product $product): View
    {
        $product->load([
            'category', 'brand', 'media',
            'variants.attributeValues.attribute', 'variants.image',
            'defaultVariant', 'ogImage',
        ])->loadCount('reviews');

        return view('admin.products.show', ['product' => $product]);
    }

    public function create(): View
    {
        $product = new Product([
            'type' => Product::TYPE_TRADING,
            'variant_mode' => Product::VARIANT_SIMPLE,
            'is_active' => true,
            'is_web_listed' => true,
        ]);

        return view('admin.products.create', [
            'product' => $product,
            'variantState' => $this->variantState($product),
            ...$this->formData(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        [$attributes, $simple, $variants, $defaultIndex, $images] = $this->extract($request);

        $product = Product::create($attributes);
        $this->writer->syncVariants($product, $attributes['variant_mode'], $simple, $variants, $defaultIndex);
        $this->writer->syncMedia($product, $images);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load(['variants.attributeValues', 'defaultVariant', 'media:id']);

        return view('admin.products.edit', [
            'product' => $product,
            'variantState' => $this->variantState($product),
            ...$this->formData(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        [$attributes, $simple, $variants, $defaultIndex, $images] = $this->extract($request);

        $product->update($attributes);
        $this->writer->syncVariants($product, $attributes['variant_mode'], $simple, $variants, $defaultIndex);
        $this->writer->syncMedia($product, $images);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Soft delete — keeps order history (order_items snapshot names) and hides
        // the product (and its variants, reached through it) from the storefront.
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product deleted.');
    }

    // Persistence helpers ------------------------------------------------------

    /** Split validated input into product attributes, simple/variable variant data and image ids. */
    private function extract(ProductRequest $request): array
    {
        $data = $request->validated();
        $simple = $data['variant'] ?? [];
        $variants = $data['variants'] ?? [];
        $defaultIndex = (int) $request->input('variant_default', 0);
        $images = $data['images'] ?? [];
        // Drop keys that aren't product columns (the builder's raw rows). The
        // transformed `specifications`/`highlights` stay in $data (both fillable).
        unset($data['variant'], $data['variants'], $data['images'], $data['specs'], $data['variant_default']);

        return [$data, $simple, $variants, $defaultIndex, $images];
    }

    // Form data ----------------------------------------------------------------

    /** Initial state for the Alpine variant builder (mode, chosen options, variant rows, default). */
    private function variantState(Product $product): array
    {
        $empty = ['mode' => $product->variant_mode ?: 'simple', 'options' => [['attributeId' => '', 'valueIds' => []]], 'variants' => [], 'defaultIndex' => 0];

        if (! $product->exists) {
            return $empty;
        }

        $variants = $product->relationLoaded('variants')
            ? $product->variants
            : $product->variants()->with('attributeValues')->get();

        $isVariable = $product->variant_mode === Product::VARIANT_VARIABLE;

        $rows = $variants->values()->map(fn (ProductVariant $v) => [
            'id' => $v->id,
            'value_ids' => $v->attributeValues->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'sku' => $v->sku,
            'retail_price' => $this->trimDecimal($v->retail_price),
            'compare_at_price' => $v->compare_at_price !== null ? $this->trimDecimal($v->compare_at_price) : '',
            'cost' => $this->trimDecimal($v->cost),
            'stock_quantity' => $this->trimDecimal($v->stock_quantity),
            'low_stock_threshold' => $this->trimDecimal($v->low_stock_threshold),
            'image_media_id' => $v->image_media_id ? (string) $v->image_media_id : '',
            'is_active' => (bool) $v->is_active,
        ])->all();

        // Reconstruct the option pickers (attribute → chosen value ids) from existing variants.
        $options = [];
        if ($isVariable) {
            $byAttribute = [];
            foreach ($variants as $v) {
                foreach ($v->attributeValues as $av) {
                    $byAttribute[$av->attribute_id] ??= [];
                    if (! in_array($av->id, $byAttribute[$av->attribute_id], true)) {
                        $byAttribute[$av->attribute_id][] = $av->id;
                    }
                }
            }
            foreach ($byAttribute as $attributeId => $valueIds) {
                $options[] = ['attributeId' => (string) $attributeId, 'valueIds' => $valueIds];
            }
        }

        return [
            'mode' => $product->variant_mode ?: 'simple',
            'options' => $options ?: [['attributeId' => '', 'valueIds' => []]],
            'variants' => $isVariable ? $rows : [],
            'defaultIndex' => (int) max(0, $variants->values()->search(fn (ProductVariant $v) => $v->is_default) ?: 0),
        ];
    }

    private function trimDecimal(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.') ?: '0';
    }

    private function formData(): array
    {
        return [
            'categoryOptions' => Category::orderBy('name')->pluck('name', 'id')->all(),
            'brandOptions' => Brand::orderBy('name')->pluck('name', 'id')->all(),
            'unitOptions' => Unit::where('is_active', true)->orderBy('sort_order')->orderBy('name')
                ->get()->mapWithKeys(fn (Unit $u) => [$u->id => "{$u->name} ({$u->code})"])->all(),
            'mediaItems' => $this->mediaItems(),
            'variationAttributes' => Attribute::where('is_variation', true)
                ->with(['values' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
                ->orderBy('name')
                ->get()
                ->map(fn (Attribute $a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'values' => $a->values->map(fn (AttributeValue $v) => [
                        'id' => $v->id,
                        'label' => $v->label ?: $v->value,
                        'color' => $v->color_hex,
                    ])->all(),
                ])->all(),
        ];
    }

    /**
     * @return Collection<int, array{id:int, url:string, title:string}>
     */
    private function mediaItems(): Collection
    {
        return Media::query()
            ->latest('id')
            ->limit(200)
            ->get(['id', 'disk', 'path', 'title'])
            ->map(fn (Media $m) => [
                'id' => $m->id,
                'url' => $m->url,
                'title' => $m->title ?: basename($m->path),
            ]);
    }
}
