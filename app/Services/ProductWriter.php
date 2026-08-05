<?php

namespace App\Services;

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

/**
 * The single writer for a product's variant matrix and gallery. Extracted from
 * ProductController so the admin form and the spreadsheet importer persist
 * variants through identical code instead of two drifting copies.
 *
 * Form semantics (pruneMissing: true) are authoritative: submitted rows are
 * upserted and variants missing from the submission are deleted. The importer
 * passes pruneMissing: false — spreadsheet rows only ever add or update, and
 * a null defaultIndex leaves each variant's is_default flag as it stands.
 */
class ProductWriter
{
    /** First free slug (soft-deleted rows still hold theirs). */
    public function uniqueSlug(string $base): string
    {
        $slug = $base;
        for ($i = 2; Product::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /** First free SKU across products and variants (both are unique columns). */
    public function uniqueSku(string $base): string
    {
        $sku = $base;
        for ($i = 2; Product::withTrashed()->where('sku', $sku)->exists() || ProductVariant::where('sku', $sku)->exists(); $i++) {
            $sku = "{$base}{$i}";
        }

        return $sku;
    }

    public function syncVariants(Product $product, string $mode, array $simple, array $variants, ?int $defaultIndex, bool $pruneMissing = true): void
    {
        if ($mode === Product::VARIANT_VARIABLE && ! empty($variants)) {
            $this->syncVariableVariants($product, $variants, $defaultIndex, $pruneMissing);
        } else {
            $this->syncSimpleVariant($product, $simple);
        }
    }

    /**
     * Single default variant — collapse any extras and drop variation
     * attributes. Collapsing is the meaning of simple mode, so it happens
     * regardless of pruneMissing.
     */
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
        ]);
        if (array_key_exists('weight', $v)) {
            $variant->weight = $v['weight']; // import-only column, see syncVariableVariants
        }
        $variant->save();
        $variant->attributeValues()->detach();

        $product->variants()->whereKeyNot($variant->id)->get()->each(function (ProductVariant $extra) {
            $extra->attributeValues()->detach();
            $extra->delete();
        });
        $product->attributes()->detach();
    }

    /** Multiple variants from the attribute matrix — upsert the given rows; drop the rest only when pruning. */
    private function syncVariableVariants(Product $product, array $rows, ?int $defaultIndex, bool $pruneMissing): void
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
                // null defaultIndex = leave existing flags alone (new rows start false).
                'is_default' => $defaultIndex === null ? (bool) ($variant->is_default ?? false) : $i === $defaultIndex,
                'is_active' => (bool) ($row['is_active'] ?? true),
            ]);
            // Import-only columns: the admin form never submits these keys, so
            // form saves leave them untouched while spreadsheet rows set them.
            foreach (['weight', 'barcode'] as $extra) {
                if (array_key_exists($extra, $row)) {
                    $variant->{$extra} = $row[$extra];
                }
            }
            $variant->save();
            $variant->attributeValues()->sync($valueIds);
            $keep[] = $variant->id;
        }

        if ($pruneMissing) {
            // Removed combinations — FK is nullOnDelete, so order history stays intact.
            $product->variants()->whereNotIn('id', $keep)->get()->each(function (ProductVariant $gone) {
                $gone->attributeValues()->detach();
                $gone->delete();
            });
        }

        // Guarantee exactly one default.
        if (! $product->variants()->where('is_default', true)->exists()) {
            $product->variants()->oldest('id')->first()?->update(['is_default' => true]);
        }

        // Link the product to the variation attributes actually in use. When rows
        // may be a partial set (import), derive from every surviving variant, not
        // just the submitted rows — otherwise untouched variants lose their links.
        $valueIdSource = $pruneMissing
            ? collect($rows)->pluck('value_ids')->flatten()->map(fn ($id) => (int) $id)->unique()
            : $product->variants()->with('attributeValues:id,attribute_id')->get()
                ->flatMap(fn (ProductVariant $v) => $v->attributeValues->pluck('id'))->unique();

        $attributeIds = AttributeValue::whereIn('id', $valueIdSource)
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
    public function syncMedia(Product $product, array $imageIds): void
    {
        $sync = [];
        foreach (array_values($imageIds) as $i => $id) {
            $sync[$id] = ['sort_order' => $i, 'is_primary' => $i === 0];
        }

        $product->media()->sync($sync);
    }
}
