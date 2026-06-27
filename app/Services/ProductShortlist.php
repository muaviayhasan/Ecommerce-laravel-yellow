<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Base for the session-backed product shortlists (wishlist, compare). Stores a
 * list of product ids and resolves them against the live catalog on read.
 */
abstract class ProductShortlist
{
    abstract protected function key(): string;

    /** Optional cap on how many products the list may hold (null = unlimited). */
    protected function limit(): ?int
    {
        return null;
    }

    /**
     * @return array<int, int>
     */
    public function ids(): array
    {
        return array_values(array_map('intval', (array) session($this->key(), [])));
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function isEmpty(): bool
    {
        return $this->ids() === [];
    }

    /**
     * Add or remove a product.
     *
     * @return array{added: bool, full: bool}
     */
    public function toggle(int $productId): array
    {
        $ids = $this->ids();

        if (in_array($productId, $ids, true)) {
            session()->put($this->key(), array_values(array_diff($ids, [$productId])));

            return ['added' => false, 'full' => false];
        }

        if ($this->limit() !== null && count($ids) >= $this->limit()) {
            return ['added' => false, 'full' => true];
        }

        $ids[] = $productId;
        session()->put($this->key(), $ids);

        return ['added' => true, 'full' => false];
    }

    public function remove(int $productId): void
    {
        session()->put($this->key(), array_values(array_diff($this->ids(), [$productId])));
    }

    public function clear(): void
    {
        session()->forget($this->key());
    }

    /**
     * The live, storefront-visible products on the list, in insertion order.
     *
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        $ids = $this->ids();
        if ($ids === []) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->webListed()
            ->whereHas('defaultVariant')
            ->with(['defaultVariant.image', 'category:id,name,slug', 'media'])
            ->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $ids, true))
            ->values();
    }
}
