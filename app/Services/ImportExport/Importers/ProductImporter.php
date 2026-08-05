<?php

namespace App\Services\ImportExport\Importers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Services\ImportExport\Exporters\ProductExporter;
use App\Services\ImportExport\ImportResult;
use App\Services\ImportExport\SpreadsheetReader;
use App\Services\ProductWriter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/**
 * Upserts products (with their full variant matrix) from an .xlsx whose
 * headers are ProductExporter::HEADERS. One spreadsheet row per variant; rows
 * sharing a product_sku form one product group and product-level columns are
 * read from the group's first row.
 *
 * Semantics:
 * - Match by SKU: products by product_sku, variants by variant_sku.
 * - Blank cells NEVER overwrite an existing value (clear fields in the product
 *   form instead); on create they fall back to sensible defaults.
 * - Variants absent from the file are left untouched (nothing is deleted) —
 *   except a product imported in `simple` mode, which collapses to one variant
 *   because that is what simple mode means.
 * - `attributes` cell = `code=value|code=value`. Unknown attribute codes fail
 *   the group; unknown values under a known attribute are created.
 * - Each product group is one transaction — a bad group rolls back alone.
 *
 * Deliberate tradeoffs (same as the admin product form): stock_quantity is
 * written directly (no stock_movements ledger rows), and AuditObserver +
 * catalog cache bumps fire per save.
 */
class ProductImporter
{
    private const TYPES = ['trading', 'manufactured', 'raw', 'service'];

    public function __construct(
        private readonly SpreadsheetReader $reader,
        private readonly ProductWriter $writer,
    ) {
    }

    public function import(string $path): ImportResult
    {
        $result = new ImportResult();

        // Group rows by product_sku, preserving first-occurrence order.
        $groups = [];
        foreach ($this->reader->rows($path, ProductExporter::HEADERS) as $rowNumber => $row) {
            $sku = trim($row['product_sku']);
            if ($sku === '') {
                $result->error($rowNumber, 'product_sku is required on every row.');

                continue;
            }
            $groups[$sku][] = ['n' => $rowNumber, 'row' => $row];
        }

        $lookups = $this->lookups();

        foreach ($groups as $sku => $rows) {
            try {
                DB::transaction(function () use ($sku, $rows, $lookups, $result) {
                    $this->importGroup((string) $sku, $rows, $lookups, $result);
                });
            } catch (RowError $e) {
                $result->error($e->row ?? $rows[0]['n'], $e->getMessage());
            }
        }

        return $result;
    }

    private function lookups(): array
    {
        return [
            'categories' => Category::pluck('id', 'slug')->all(),
            'brands' => Brand::pluck('id', 'slug')->all(),
            'units' => Unit::get(['id', 'name'])->mapWithKeys(fn ($u) => [mb_strtolower($u->name) => $u->id])->all(),
            'attributes' => Attribute::with('values')->get()->keyBy(fn ($a) => mb_strtolower($a->code)),
            'media' => Media::pluck('id', 'path')->all(),
        ];
    }

    /** @param array<int, array{n: int, row: array}> $rows */
    private function importGroup(string $sku, array $rows, array $lookups, ImportResult $result): void
    {
        $first = $rows[0]['row'];
        $firstRowNumber = $rows[0]['n'];

        $product = Product::withTrashed()->where('sku', $sku)->first();
        if ($product?->trashed()) {
            throw new RowError("SKU '{$sku}' belongs to a deleted product — restore it first.", $firstRowNumber);
        }

        $attrs = $this->productAttributes($sku, $first, $product, $lookups, $firstRowNumber);

        $mode = $attrs['variant_mode'];
        if ($mode === Product::VARIANT_SIMPLE && count($rows) > 1) {
            throw new RowError("'{$sku}' is marked simple but has " . count($rows) . ' rows — simple products take exactly one row.', $firstRowNumber);
        }

        $isNew = $product === null;
        if ($isNew) {
            $product = Product::create($attrs);
        } else {
            $product->update($attrs);
        }

        if ($mode === Product::VARIANT_VARIABLE) {
            [$variantRows, $defaultIndex] = $this->variableRows($product, $rows, $lookups);
            $this->writer->syncVariants($product, $mode, [], $variantRows, $defaultIndex, pruneMissing: false);
        } else {
            $simple = $this->simpleRow($product, $rows[0]);
            $this->writer->syncVariants($product, $mode, $simple, [], null);
        }

        if (trim($first['images']) !== '') {
            $ids = [];
            foreach (explode('|', $first['images']) as $imagePath) {
                $imagePath = trim($imagePath);
                if ($imagePath === '') {
                    continue;
                }
                if (! isset($lookups['media'][$imagePath])) {
                    $result->warning($firstRowNumber, "Image '{$imagePath}' not found in the gallery — skipped.");

                    continue;
                }
                $ids[] = $lookups['media'][$imagePath];
            }
            if ($ids !== []) {
                $this->writer->syncMedia($product, $ids);
            }
        }

        $isNew ? $result->created++ : $result->updated++;
    }

    /** Product-level columns from the group's first row; blank = keep existing / default. */
    private function productAttributes(string $sku, array $r, ?Product $existing, array $lookups, int $rowNumber): array
    {
        $keep = fn (string $col, mixed $default = null) => $r[$col] !== '' ? $r[$col] : ($existing?->{$col} ?? $default);

        $categorySlug = trim($r['category_slug']);
        if ($categorySlug !== '' && ! isset($lookups['categories'][$categorySlug])) {
            throw new RowError("Category '{$categorySlug}' was not found — create it (or import categories) first.", $rowNumber);
        }
        if ($categorySlug === '' && ! $existing) {
            throw new RowError('category_slug is required for new products.', $rowNumber);
        }

        $brandSlug = trim($r['brand_slug']);
        if ($brandSlug !== '' && ! isset($lookups['brands'][$brandSlug])) {
            throw new RowError("Brand '{$brandSlug}' was not found.", $rowNumber);
        }

        $unitName = mb_strtolower(trim($r['unit']));
        if ($unitName !== '' && ! isset($lookups['units'][$unitName])) {
            throw new RowError("Unit '{$r['unit']}' was not found.", $rowNumber);
        }

        $specifications = $existing?->specifications;
        if (trim($r['specifications']) !== '') {
            $specifications = json_decode($r['specifications'], true);
            if (! is_array($specifications)) {
                throw new RowError('specifications is not valid JSON (expected {"Group":{"Label":"Value"}}).', $rowNumber);
            }
        }

        $publishedAt = $existing?->published_at;
        if (trim($r['published_at']) !== '') {
            try {
                $publishedAt = Carbon::parse($r['published_at']);
            } catch (Throwable) {
                throw new RowError("published_at '{$r['published_at']}' is not a valid date (use Y-m-d H:i:s).", $rowNumber);
            }
        }

        $attrs = [
            'sku' => $sku,
            'name' => $keep('name'),
            'category_id' => $categorySlug !== '' ? $lookups['categories'][$categorySlug] : $existing->category_id,
            'brand_id' => $brandSlug !== '' ? $lookups['brands'][$brandSlug] : $existing?->brand_id,
            'unit_id' => $unitName !== '' ? $lookups['units'][$unitName] : $existing?->unit_id,
            'type' => mb_strtolower((string) $keep('type', Product::TYPE_TRADING)),
            'variant_mode' => mb_strtolower((string) $keep('variant_mode', Product::VARIANT_SIMPLE)),
            'short_description' => $keep('short_description'),
            'description' => $keep('description'),
            'highlights' => trim($r['highlights']) !== ''
                ? collect(explode('|', $r['highlights']))->map(fn ($h) => trim($h))->filter()->values()->all()
                : ($existing?->highlights ?? []),
            'specifications' => $specifications,
            'base_price' => $keep('base_price'),
            'markup_percent' => $keep('markup_percent'),
            'warranty' => $keep('warranty'),
            'return_policy' => $keep('return_policy'),
            'video_url' => $keep('video_url'),
            'length' => $keep('length'),
            'width' => $keep('width'),
            'height' => $keep('height'),
            'published_at' => $publishedAt,
            'meta_title' => $keep('meta_title'),
            'meta_description' => $keep('meta_description'),
            'meta_keywords' => $keep('meta_keywords'),
            'canonical_url' => $keep('canonical_url'),
        ];

        foreach ([
            'is_active' => true, 'is_featured' => false, 'is_trending' => false, 'is_bestseller' => false,
            'is_pinned' => false, 'is_web_listed' => false, 'no_index' => false,
            'is_stock_tracked' => true, 'is_purchasable' => true, 'is_manufacturable' => false, 'is_sellable' => true,
        ] as $flag => $default) {
            $value = SpreadsheetReader::bool($r[$flag]);
            $attrs[$flag] = $value ?? ($existing?->{$flag} ?? $default);
        }

        $validator = Validator::make($attrs, [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . implode(',', self::TYPES)],
            'variant_mode' => ['required', 'in:simple,variable'],
            // DB column is varchar(255); ProductRequest's max:500 is a latent truncation bug we don't copy.
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'base_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'markup_percent' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'return_policy' => ['nullable', 'string', 'max:5000'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new RowError($validator->errors()->first(), $rowNumber);
        }

        // Slug: given, or existing, or generated from the name — always uniqued.
        $slug = trim($r['slug']) !== '' ? Str::slug($r['slug']) : ($existing?->slug ?? Str::slug((string) $attrs['name']));
        if (! $existing || $slug !== $existing->slug) {
            $taken = Product::withTrashed()->where('slug', $slug)->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists();
            $attrs['slug'] = $taken ? $this->writer->uniqueSlug($slug) : $slug;
        } else {
            $attrs['slug'] = $slug;
        }

        return $attrs;
    }

    /**
     * @param array<int, array{n: int, row: array}> $rows
     * @return array{0: array, 1: ?int} ProductWriter variant rows + default index
     */
    private function variableRows(Product $product, array $rows, array $lookups): array
    {
        $variantRows = [];
        $defaultIndex = null;

        foreach ($rows as $i => $item) {
            $r = $item['row'];
            $rowNumber = $item['n'];

            $variantSku = trim($r['variant_sku']);
            $existing = null;
            if ($variantSku !== '') {
                $existing = ProductVariant::where('sku', $variantSku)->first();
                if ($existing && $existing->product_id !== $product->id) {
                    throw new RowError("Variant SKU '{$variantSku}' already belongs to another product.", $rowNumber);
                }
            }

            $valueIds = $this->resolveAttributeValues($r['attributes'], $lookups, $rowNumber);
            if ($valueIds === []) {
                throw new RowError('Variable-product rows need an attributes cell like color=red|size=m.', $rowNumber);
            }

            $keep = fn (string $col, mixed $default) => $r[$col] !== '' ? $r[$col] : ($existing?->{$this->variantColumn($col)} ?? $default);

            $variantRows[] = [
                'id' => $existing?->id,
                'sku' => $variantSku !== '' ? $variantSku : null,
                'value_ids' => $valueIds,
                'cost' => $keep('cost', 0),
                'retail_price' => $keep('retail_price', 0),
                'wholesale_price' => $keep('wholesale_price', null),
                'compare_at_price' => $keep('compare_at_price', null),
                'stock_quantity' => $keep('stock_quantity', 0),
                'low_stock_threshold' => $keep('low_stock_threshold', 0),
                'weight' => $keep('weight', null),
                'barcode' => $keep('barcode', null),
                'image_media_id' => $existing?->image_media_id,
                'is_active' => SpreadsheetReader::bool($r['variant_is_active']) ?? ($existing?->is_active ?? true),
            ];

            $this->validateVariantNumbers(end($variantRows), $rowNumber);

            if ($defaultIndex === null && SpreadsheetReader::bool($r['variant_is_default']) === true) {
                $defaultIndex = $i;
            }
        }

        return [$variantRows, $defaultIndex];
    }

    /** @param array{n: int, row: array} $item */
    private function simpleRow(Product $product, array $item): array
    {
        $r = $item['row'];
        $existing = $product->variants()->where('is_default', true)->first()
            ?? $product->variants()->oldest('id')->first();

        $keep = fn (string $col, mixed $default) => $r[$col] !== '' ? $r[$col] : ($existing?->{$this->variantColumn($col)} ?? $default);

        $simple = [
            'cost' => $keep('cost', 0),
            'retail_price' => $keep('retail_price', 0),
            'wholesale_price' => $keep('wholesale_price', null),
            'compare_at_price' => $keep('compare_at_price', null),
            'stock_quantity' => $keep('stock_quantity', 0),
            'low_stock_threshold' => $keep('low_stock_threshold', 0),
            'weight' => $keep('weight', null),
            'barcode' => $keep('barcode', null),
        ];

        $this->validateVariantNumbers($simple, $item['n']);

        return $simple;
    }

    /** Spreadsheet variant columns map 1:1 onto product_variants columns. */
    private function variantColumn(string $header): string
    {
        return $header;
    }

    /** @throws RowError */
    private function validateVariantNumbers(array $data, int $rowNumber): void
    {
        $validator = Validator::make($data, [
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'retail_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            throw new RowError($validator->errors()->first(), $rowNumber);
        }
    }

    /**
     * Parse `code=value|code=value` into attribute-value ids. Unknown codes
     * throw; unknown values under a known attribute are created (matched
     * case-insensitively against both value and label first).
     *
     * @return list<int>
     *
     * @throws RowError
     */
    private function resolveAttributeValues(string $cell, array $lookups, int $rowNumber): array
    {
        $cell = trim($cell);
        if ($cell === '') {
            return [];
        }

        $ids = [];
        foreach (explode('|', $cell) as $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                continue;
            }
            if (! str_contains($pair, '=')) {
                throw new RowError("attributes segment '{$pair}' is not code=value.", $rowNumber);
            }

            [$code, $value] = array_map('trim', explode('=', $pair, 2));
            $attribute = $lookups['attributes'][mb_strtolower($code)] ?? null;

            if (! $attribute) {
                $known = $lookups['attributes']->keys()->implode(', ');
                throw new RowError("Unknown attribute code '{$code}' (known: {$known}). Create the attribute first.", $rowNumber);
            }

            $match = $attribute->values->first(fn ($av) => mb_strtolower($av->value) === mb_strtolower($value)
                || mb_strtolower((string) $av->label) === mb_strtolower($value));

            if (! $match) {
                $match = AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value' => Str::slug($value),
                    'label' => $value,
                    'sort_order' => ((int) $attribute->values->max('sort_order')) + 1,
                ]);
                $attribute->values->push($match); // visible to later rows in this run
            }

            $ids[] = $match->id;
        }

        return array_values(array_unique($ids));
    }
}
