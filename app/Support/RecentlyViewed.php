<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;

/**
 * Tracks the products a visitor has recently viewed using a cookie on the local
 * browser (most-recent first, de-duplicated, capped). Read on the home page to
 * render the "Your Recently Viewed Products" strip.
 */
class RecentlyViewed
{
    private const COOKIE = 'recently_viewed';
    private const MAX = 12;
    private const DAYS = 60 * 24 * 30; // 30 days (minutes)

    /** Record a product view — pushes it to the front, unique, capped at MAX. */
    public static function add(int $productId): void
    {
        $ids = array_values(array_filter(self::ids(), fn ($id) => $id !== $productId));
        array_unshift($ids, $productId);
        $ids = array_slice($ids, 0, self::MAX);

        Cookie::queue(self::COOKIE, json_encode($ids), self::DAYS);
    }

    /**
     * Product ids in most-recently-viewed order.
     *
     * @return list<int>
     */
    public static function ids(): array
    {
        $raw = request()->cookie(self::COOKIE);
        if (! $raw) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded)
            ? array_values(array_unique(array_filter(array_map('intval', $decoded))))
            : [];
    }

    /**
     * Recently-viewed products as storefront cards, newest first, order preserved.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function cards(int $limit = 6, ?int $excludeId = null): Collection
    {
        $ids = self::ids();
        if ($excludeId !== null) {
            $ids = array_values(array_filter($ids, fn ($id) => $id !== $excludeId));
        }
        $ids = array_slice($ids, 0, $limit);

        if ($ids === []) {
            return collect();
        }

        $byId = Storefront::query()->whereIn('products.id', $ids)->get()->keyBy('id');

        // Reorder to match the cookie (whereIn ignores order) and drop any that are
        // no longer web-listed / in stock.
        $ordered = collect($ids)->map(fn ($id) => $byId->get($id))->filter()->values();

        return Storefront::cards($ordered);
    }
}
