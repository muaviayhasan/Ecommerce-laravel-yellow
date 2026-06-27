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
                'category.parent',
                'media',
                'reviews' => fn ($q) => $q->where('is_approved', true)->with('user:id,name')->latest(),
            ])
            ->firstOrFail();

        $variant = $product->defaultVariant;
        $retail = (float) ($variant?->retail_price ?? 0);
        $compareRaw = $variant?->compare_at_price;
        $compare = $compareRaw !== null && (float) $compareRaw > $retail ? (float) $compareRaw : null;

        $gallery = $product->media->pluck('url')->filter()->values()->all();
        if ($gallery === []) {
            $gallery = [$variant?->image?->url ?? Storefront::placeholder()];
        }

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
}
