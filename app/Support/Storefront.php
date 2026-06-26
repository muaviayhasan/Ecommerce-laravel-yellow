<?php

namespace App\Support;

use App\Models\Product;
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
            'name' => $product->name,
            'category' => $product->category?->name,
            'price' => $retail,
            'compare' => $compare,
            'image' => $product->media->first()?->url ?? $variant?->image?->url ?? self::placeholder(),
            'url' => route('product.show', $product->slug),
            'slug' => $product->slug,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>|\Illuminate\Database\Eloquent\Collection<int, Product>  $products
     * @return Collection<int, array<string, mixed>>
     */
    public static function cards($products): Collection
    {
        return $products->map(fn (Product $product) => self::card($product))->values();
    }

    public static function placeholder(): string
    {
        return 'https://placehold.co/400x400/f1f5f9/94a3b8?text=No+Image';
    }
}
