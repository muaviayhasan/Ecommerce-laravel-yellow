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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:products.view', only: ['index', 'show']),
            new Middleware('can:products.create', only: ['create', 'store']),
            new Middleware('can:products.edit', only: ['edit', 'update']),
            new Middleware('can:products.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'defaultVariant', 'media:id,disk,path'])
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
            })
            ->latest('id')
            ->paginate(per_page())
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'brands' => Brand::orderBy('name')->pluck('name', 'id'),
            'stats' => [
                'total' => Product::count(),
                'active' => Product::where('is_active', true)->count(),
                'web_listed' => Product::webListed()->count(),
                'featured' => Product::where('is_featured', true)->count(),
            ],
            'filters' => $request->only('search', 'category', 'brand', 'status'),
        ]);
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
        $this->syncVariants($product, $attributes['variant_mode'], $simple, $variants, $defaultIndex);
        $this->syncMedia($product, $images);

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
        $this->syncVariants($product, $attributes['variant_mode'], $simple, $variants, $defaultIndex);
        $this->syncMedia($product, $images);

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

    private function syncVariants(Product $product, string $mode, array $simple, array $variants, int $defaultIndex): void
    {
        if ($mode === Product::VARIANT_VARIABLE && ! empty($variants)) {
            $this->syncVariableVariants($product, $variants, $defaultIndex);
        } else {
            $this->syncSimpleVariant($product, $simple);
        }
    }

    /** Single default variant — collapse any extras and drop variation attributes. */
    private function syncSimpleVariant(Product $product, array $v): void
    {
        $variant = $product->variants()->where('is_default', true)->first()
            ?? $product->variants()->oldest('id')->first()
            ?? new ProductVariant(['is_default' => true]);

        $variant->fill([
            'product_id' => $product->id,
            'sku' => $variant->sku ?: $product->sku . '-D',
            'cost' => $v['cost'] ?? 0,
            'retail_price' => $v['retail_price'] ?? 0,
            'wholesale_price' => $v['wholesale_price'] ?? null,
            'compare_at_price' => $v['compare_at_price'] ?? null,
            'stock_quantity' => $v['stock_quantity'] ?? 0,
            'low_stock_threshold' => $v['low_stock_threshold'] ?? 0,
            'barcode' => $v['barcode'] ?? null,
            'is_default' => true,
            'is_active' => true,
        ])->save();
        $variant->attributeValues()->detach();

        $product->variants()->whereKeyNot($variant->id)->get()->each(function (ProductVariant $extra) {
            $extra->attributeValues()->detach();
            $extra->delete();
        });
        $product->attributes()->detach();
    }

    /** Multiple variants from the attribute matrix — upsert submitted rows, drop the rest. */
    private function syncVariableVariants(Product $product, array $rows, int $defaultIndex): void
    {
        $rows = array_values($rows);
        $keep = [];
        $usedSkus = [];

        foreach ($rows as $i => $row) {
            $valueIds = collect($row['value_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();

            $variant = (! empty($row['id']) ? $product->variants()->find($row['id']) : null)
                ?? new ProductVariant(['product_id' => $product->id]);

            $base = filled($row['sku'] ?? null) ? $row['sku'] : $this->variantSkuBase($product, $valueIds);
            $sku = $this->uniqueVariantSku($base, $usedSkus, $variant->id);
            $usedSkus[] = $sku;

            $variant->fill([
                'product_id' => $product->id,
                'sku' => $sku,
                'cost' => $row['cost'] ?? 0,
                'retail_price' => $row['retail_price'] ?? 0,
                'wholesale_price' => $row['wholesale_price'] ?? null,
                'compare_at_price' => $row['compare_at_price'] ?? null,
                'stock_quantity' => $row['stock_quantity'] ?? 0,
                'low_stock_threshold' => $row['low_stock_threshold'] ?? 0,
                'image_media_id' => filled($row['image_media_id'] ?? null) ? (int) $row['image_media_id'] : null,
                'is_default' => $i === $defaultIndex,
                'is_active' => (bool) ($row['is_active'] ?? true),
            ])->save();
            $variant->attributeValues()->sync($valueIds);
            $keep[] = $variant->id;
        }

        // Removed combinations — FK is nullOnDelete, so order history stays intact.
        $product->variants()->whereNotIn('id', $keep)->get()->each(function (ProductVariant $gone) {
            $gone->attributeValues()->detach();
            $gone->delete();
        });

        // Guarantee exactly one default.
        if (! $product->variants()->where('is_default', true)->exists()) {
            $product->variants()->oldest('id')->first()?->update(['is_default' => true]);
        }

        // Link the product to the variation attributes that were actually used.
        $attributeIds = AttributeValue::whereIn('id', collect($rows)->pluck('value_ids')->flatten()->map(fn ($id) => (int) $id)->unique())
            ->pluck('attribute_id')->unique()->values()->all();
        $product->attributes()->sync($attributeIds);
    }

    private function variantSkuBase(Product $product, array $valueIds): string
    {
        $codes = AttributeValue::whereIn('id', $valueIds)->orderBy('id')
            ->pluck('value')
            ->map(fn ($v) => Str::upper(Str::slug((string) $v)))
            ->implode('-');

        return trim(($product->sku ?: 'VAR') . ($codes !== '' ? '-' . $codes : ''), '-');
    }

    /** Unique among the rows in this submission and the variants table (ignoring the row being saved). */
    private function uniqueVariantSku(string $base, array $used, ?int $ignoreId): string
    {
        $base = $base !== '' ? $base : 'VAR';
        $sku = $base;
        $i = 2;

        $taken = fn (string $candidate) => in_array($candidate, $used, true)
            || ProductVariant::where('sku', $candidate)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists();

        while ($taken($sku)) {
            $sku = "{$base}-{$i}";
            $i++;
        }

        return $sku;
    }

    /** Sync the product_media pivot — order preserved, first image flagged primary. */
    private function syncMedia(Product $product, array $imageIds): void
    {
        $sync = [];
        foreach (array_values($imageIds) as $i => $id) {
            $sync[$id] = ['sort_order' => $i, 'is_primary' => $i === 0];
        }

        $product->media()->sync($sync);
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
