<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Deal;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Maps real Product models into the lightweight card shape the storefront views
 * consume ({name, category, price, compare, image, url}). The views read every
 * field with data_get(), so the same arrays drop straight in where the old
 * sample catalog used to sit.
 */
class Storefront
{
    /** Base query for storefront-visible products with everything a card needs. */
    public static function query(): Builder
    {
        return Product::query()
            ->webListed()
            ->whereHas('defaultVariant')
            ->with(['defaultVariant.image', 'category:id,name,slug', 'media']);
    }

    /** Only products whose default variant is marked down. */
    public static function onSaleQuery(): Builder
    {
        return self::query()->whereHas('defaultVariant', fn ($q) => $q->whereColumn('compare_at_price', '>', 'retail_price'));
    }

    /**
     * @return array{id:int, name:string, category:?string, price:float, compare:?float, image:string, url:string, slug:string}
     */
    public static function card(Product $product): array
    {
        $variant = $product->defaultVariant;
        $retail = (float) ($variant?->retail_price ?? 0);
        $compareRaw = $variant?->compare_at_price;
        $compare = $compareRaw !== null && (float) $compareRaw > $retail ? (float) $compareRaw : null;

        return [
            'id' => $product->id,
            'variant_id' => $variant?->id,
            'name' => $product->name,
            'category' => $product->category?->name,
            'price' => $retail,
            'compare' => $compare,
            'in_stock' => self::variantSellable($variant, (bool) $product->is_stock_tracked),
            'image' => $product->media->first()?->thumbUrl(400) ?? $variant?->image?->thumbUrl(400) ?? self::placeholder(),
            'url' => route('product.show', $product->slug),
            'slug' => $product->slug,
        ];
    }

    /**
     * Whether a card's add-to-cart should be active. Mirrors the cart's add rule
     * (§CartController): a stock-tracked variant with nothing on hand — and no
     * overselling allowed — can't be added, so the card shows a crossed-out cart.
     */
    private static function variantSellable(?ProductVariant $variant, bool $tracked): bool
    {
        if (! $variant) {
            return true; // no add button is shown anyway — the card links to the product page
        }

        if (! $tracked || (bool) setting('inventory', 'allow_negative_stock', false)) {
            return true;
        }

        return (int) floor((float) $variant->stock_quantity) > 0;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>|\Illuminate\Database\Eloquent\Collection<int, Product>  $products
     * @return Collection<int, array<string, mixed>>
     */
    public static function cards($products): Collection
    {
        return $products->map(fn (Product $product) => self::card($product))->values();
    }

    /**
     * The three product columns rendered by <x-storefront.product-columns>
     * (Featured / Top Selling / On-sale). Featured and Top Selling fall back to
     * the latest products so a thin catalog never shows a blank column. This is
     * the single source of truth, so the section is identical on every page.
     *
     * @return array<int, array{title: string, items: Collection<int, array<string, mixed>>, rating: int|null}>
     */
    public static function promoColumns(int $perColumn = 3): array
    {
        $latest = self::cards(self::query()->latest('published_at')->take($perColumn)->get());
        $orLatest = fn (Collection $cards): Collection => $cards->isEmpty() ? $latest : $cards;

        return [
            ['title' => 'Featured Products', 'items' => $orLatest(self::cards(self::query()->featured()->latest('published_at')->take($perColumn)->get())), 'rating' => null],
            ['title' => 'Top Selling Products', 'items' => $orLatest(self::cards(self::query()->bestseller()->latest('published_at')->take($perColumn)->get())), 'rating' => null],
            ['title' => 'On-sale Products', 'items' => self::cards(self::onSaleQuery()->take($perColumn)->get()), 'rating' => 5],
        ];
    }

    /**
     * A representative image for a category — its own image, else the newest
     * product within it or its children. Powers the promo banners so they mirror
     * the live catalog. Null when nothing suitable exists.
     */
    public static function categoryImage(string $slug): ?string
    {
        $category = Category::query()->where('slug', $slug)->with('image')->first();

        if ($category?->image?->url) {
            return $category->image->thumbUrl(400);
        }

        $slugs = $category
            ? collect([$category->slug])->merge($category->children()->pluck('slug'))->all()
            : [$slug];

        return self::cards(
            self::query()
                ->whereHas('category', fn ($q) => $q->whereIn('slug', $slugs))
                ->latest('published_at')->take(1)->get()
        )->first()['image'] ?? null;
    }

    // --- Deals (home-page promotions) -------------------------------------------

    /**
     * Home-page deal cards, in admin order. Live + show_on_home deals only.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function homeDeals(int $limit = 8): Collection
    {
        return Deal::forHome()
            ->with(['image', 'items.variant.product:id,name', 'items.variant.product.media', 'items.variant.image'])
            ->take($limit)->get()
            ->map(fn (Deal $d) => self::dealCard($d))
            ->filter(fn ($c) => $c['items_count'] > 0)
            ->values();
    }

    /**
     * All live deals as cards, in admin order — for the /deals index page.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function liveDeals(): Collection
    {
        return Deal::live()
            ->with(['image', 'items.variant.product:id,name', 'items.variant.product.media', 'items.variant.image'])
            ->orderBy('sort_order')->orderByDesc('id')->get()
            ->map(fn (Deal $d) => self::dealCard($d))
            ->filter(fn ($c) => $c['items_count'] > 0)
            ->values();
    }

    /** The single spotlight deal card, or null. */
    public static function spotlightDeal(): ?array
    {
        $deal = Deal::live()->where('is_spotlight', true)
            ->with(['image', 'items.variant.product:id,name', 'items.variant.product.media', 'items.variant.image'])
            ->first();

        if (! $deal) {
            return null;
        }

        $card = self::dealCard($deal);

        return $card['items_count'] > 0 ? $card : null;
    }

    /** Map a Deal into the card shape the storefront deal blocks consume. */
    public static function dealCard(Deal $deal): array
    {
        $firstVariant = $deal->items->first()?->variant;
        $image = $deal->image?->thumbUrl(400)
            ?? $firstVariant?->image?->thumbUrl(400)
            ?? ($firstVariant?->product?->media?->first()?->thumbUrl(400))
            ?? self::placeholder();

        $subtotal = $deal->retailTotal();
        $discount = $deal->discountAmount();
        $discountLabel = (float) $deal->discount_value <= 0
            ? null
            : ($deal->discount_type === 'percent'
                ? 'Save ' . rtrim(rtrim(number_format((float) $deal->discount_value, 2), '0'), '.') . '%'
                : 'Save ' . format_money($discount));

        return [
            'id' => $deal->id,
            'name' => $deal->name,
            'slug' => $deal->slug,
            'description' => $deal->description,
            'url' => route('deal.show', $deal->slug),
            'image' => $image,
            'items_count' => $deal->items->count(),
            'subtotal' => $subtotal,
            'total' => $deal->dealTotal(),
            'discount_amount' => $discount,
            'discount_label' => $discountLabel,
        ];
    }

    // --- Per-variant listing (shop grid shows one card per active variant) -------

    /** Active variants of storefront-visible products, with everything a card needs. */
    public static function variantQuery(): Builder
    {
        return ProductVariant::query()
            ->where('product_variants.is_active', true)
            ->whereHas('product', fn ($p) => $p->webListed())
            ->with([
                'product:id,name,slug,category_id',
                'product.category:id,name,slug',
                'product.media',
                'image',
                'attributeValues:id,attribute_id,value,label,sort_order',
            ]);
    }

    /**
     * @return array{id:int, variant_id:int, name:string, variant_label:?string, category:?string, price:float, compare:?float, image:string, url:string, slug:string}
     */
    public static function variantCard(ProductVariant $variant): array
    {
        $product = $variant->product;
        $retail = (float) $variant->retail_price;
        $compareRaw = $variant->compare_at_price;
        $compare = $compareRaw !== null && (float) $compareRaw > $retail ? (float) $compareRaw : null;

        // e.g. "White" or "White / Large" — distinguishes cards of the same product.
        $label = $variant->attributeValues
            ->map(fn ($v) => $v->label ?: $v->value)
            ->filter()->implode(' / ');

        return [
            'id' => $product->id,
            'variant_id' => $variant->id,
            'name' => $product->name,
            'variant_label' => $label ?: null,
            'category' => $product->category?->name,
            'price' => $retail,
            'compare' => $compare,
            'in_stock' => self::variantSellable($variant, (bool) $product->is_stock_tracked),
            'image' => $variant->image?->thumbUrl(400) ?? $product->media->first()?->thumbUrl(400) ?? self::placeholder(),
            'url' => route('product.show', $product->slug) . '?variant=' . $variant->id,
            'slug' => $product->slug,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ProductVariant>|\Illuminate\Database\Eloquent\Collection<int, ProductVariant>  $variants
     * @return Collection<int, array<string, mixed>>
     */
    public static function variantCards($variants): Collection
    {
        return $variants->map(fn (ProductVariant $v) => self::variantCard($v))->values();
    }

    public static function placeholder(): string
    {
        return 'https://placehold.co/400x400/f1f5f9/94a3b8?text=No+Image';
    }
}
