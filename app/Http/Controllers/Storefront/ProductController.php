<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Storefront;
use Illuminate\View\View;

class ProductController extends Controller
{
    /** Product detail page — real product, gallery, specs, highlights and approved reviews. */
    public function show(string $slug): View
    {
        $product = Product::query()
            ->webListed()
            ->where('slug', $slug)
            ->with([
                'defaultVariant.image',
                'variants' => fn ($q) => $q->where('is_active', true),
                'variants.attributeValues.attribute',
                'variants.image',
                'category.parent',
                'media',
                'reviews' => fn ($q) => $q->where('is_approved', true)->with('user:id,name')->latest(),
            ])
            ->firstOrFail();

        $activeVariants = $product->variants->where('is_active', true)->values();

        // The card shows whichever variant was clicked from the shop (?variant=), else the default.
        $requested = (int) request('variant');
        $variant = $activeVariants->firstWhere('id', $requested)
            ?? $product->defaultVariant
            ?? $activeVariants->first();
        $retail = (float) ($variant?->retail_price ?? 0);
        $compareRaw = $variant?->compare_at_price;
        $compare = $compareRaw !== null && (float) $compareRaw > $retail ? (float) $compareRaw : null;

        // Gallery = product media + any per-variant images, so selecting a colour
        // can switch the main image to its own photo.
        $gallery = $product->media->pluck('url')
            ->merge($activeVariants->map(fn ($v) => $v->image?->url))
            ->filter()->unique()->values()->all();
        if ($gallery === []) {
            $gallery = [Storefront::placeholder()];
        }

        // Variation options (colour / size / …) + the per-variant matrix for the picker.
        [$variantOptions, $variantMatrix] = $this->variantData($activeVariants);

        $crumbs = collect([$product->category?->parent?->name, $product->category?->name])->filter()->implode(', ');

        $reviews = $product->reviews;
        $reviewsCount = $reviews->count();
        $avg = $reviewsCount ? round((float) $reviews->avg('rating'), 1) : 0.0;

        $card = [
            'id' => $product->id,
            'variant_id' => $variant?->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category?->name,
            'categories' => $crumbs ?: ($product->category?->name ?? 'Shop'),
            'price' => $retail,
            'compare' => $compare,
            'image' => $gallery[0],
            'gallery' => $gallery,
            'url' => route('product.show', $product->slug),
            'sku' => $variant?->sku ?? $product->sku,
            'stock' => (float) ($variant?->stock_quantity ?? 0),
            'availability' => ($variant && (float) $variant->stock_quantity > 0) ? 'In stock' : 'Out of stock',
            'rating' => (int) round($avg),
            'avg_rating' => $avg,
            'reviews_count' => $reviewsCount,
            'features' => array_values((array) ($product->highlights ?? [])),
            'specifications' => (array) ($product->specifications ?? []),
            'description' => $product->description,
            'short_description' => $product->short_description,
            'warranty' => $product->warranty,
        ];

        $latestOthers = Storefront::cards(
            Storefront::query()->where('products.id', '!=', $product->id)->latest('published_at')->take(8)->get()
        );

        $related = Storefront::cards(
            Storefront::query()->where('products.id', '!=', $product->id)
                ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
                ->latest('published_at')->take(5)->get()
        );
        if ($related->isEmpty()) {
            $related = $latestOthers->take(5)->values();
        }

        $userReview = auth()->check()
            ? \App\Models\Review::where('product_id', $product->id)->where('user_id', auth()->id())->first()
            : null;

        return view('storefront.product', [
            'product' => $card,
            'variantOptions' => $variantOptions,
            'variantMatrix' => $variantMatrix,
            'selectedVariant' => $variant?->id,
            'reviews' => $reviews,
            'userReview' => $userReview,
            'accessories' => $latestOthers->take(4)->values(),
            'related' => $related,
            'moreProducts' => $latestOthers->reverse()->take(4)->values(),
            'latest' => Storefront::cards(Storefront::query()->latest('published_at')->take(3)->get()),
            'featured' => Storefront::cards(Storefront::query()->featured()->take(2)->get()),
            'topSelling' => Storefront::cards(Storefront::query()->bestseller()->take(2)->get()),
            'onSale' => Storefront::cards(Storefront::onSaleQuery()->take(1)->get()),
        ]);
    }

    /**
     * Build the variant picker data from a product's active variants:
     *  - options: variation attributes → their values (swatch/size), for the UI
     *  - matrix:  each variant → price/compare/sku/stock/image + its chosen values
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\ProductVariant>  $variants
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function variantData($variants): array
    {
        $options = [];
        $matrix = [];

        foreach ($variants as $v) {
            $picked = [];

            foreach ($v->attributeValues as $av) {
                if (! $av->attribute?->is_variation) {
                    continue;
                }

                $aid = $av->attribute_id;
                $picked[(string) $aid] = $av->id;

                $options[$aid] ??= [
                    'id' => $aid,
                    'name' => $av->attribute->name,
                    'sort' => $av->attribute->sort_order ?? 0,
                    'values' => [],
                ];
                $options[$aid]['values'][$av->id] ??= [
                    'id' => $av->id,
                    'label' => $av->label ?: $av->value,
                    'color_hex' => $av->color_hex,
                    'image' => $av->image?->url,
                    'sort' => $av->sort_order ?? 0,
                ];
            }

            $retail = (float) $v->retail_price;
            $compareRaw = $v->compare_at_price;

            $matrix[] = [
                'id' => $v->id,
                'price' => $retail,
                'compare' => $compareRaw !== null && (float) $compareRaw > $retail ? (float) $compareRaw : null,
                'sku' => $v->sku,
                'stock' => (float) $v->stock_quantity,
                'image' => $v->image?->url,
                'options' => (object) $picked,
            ];
        }

        // Order attributes and their values by the admin's sort order.
        $options = collect($options)->sortBy('sort')->map(function ($group) {
            $group['values'] = collect($group['values'])->sortBy('sort')->values()->all();

            return $group;
        })->values()->all();

        return [$options, $matrix];
    }
}
