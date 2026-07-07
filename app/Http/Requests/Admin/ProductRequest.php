<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:products.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = (string) $this->input('name');

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_web_listed' => $this->boolean('is_web_listed'),
            'is_stock_tracked' => $this->boolean('is_stock_tracked'),
            'is_featured' => $this->boolean('is_featured'),
            'is_trending' => $this->boolean('is_trending'),
            'is_bestseller' => $this->boolean('is_bestseller'),
            'no_index' => $this->boolean('no_index'),
            // Empty <select>s → real null so `nullable` short-circuits `exists`.
            'brand_id' => $this->filled('brand_id') ? $this->input('brand_id') : null,
            'unit_id' => $this->filled('unit_id') ? $this->input('unit_id') : null,
            'base_price' => $this->filled('base_price') ? $this->input('base_price') : null,
        ]);

        // "Published" toggle → published_at timestamp (preserve the original date on edit).
        $existing = $this->route('product')?->published_at;
        $this->merge([
            'published_at' => $this->boolean('published') ? ($existing ?: now()) : null,
        ]);

        if (blank($this->input('slug')) && filled($name)) {
            $this->merge(['slug' => $this->uniqueValue('slug', Str::slug($name))]);
        }
        if (blank($this->input('sku')) && filled($name)) {
            $this->merge(['sku' => $this->uniqueValue('sku', 'PRD-' . Str::upper(Str::slug($name)))]);
        }

        $mode = $this->input('variant_mode') === Product::VARIANT_VARIABLE
            ? Product::VARIANT_VARIABLE
            : Product::VARIANT_SIMPLE;
        $this->merge(['variant_mode' => $mode]);

        // Ignore the input block belonging to the mode that isn't active.
        $this->merge($mode === Product::VARIANT_VARIABLE ? ['variant' => []] : ['variants' => []]);

        // Dropship (non-tracked) products never hold stock — force on-hand to zero.
        if (! $this->boolean('is_stock_tracked')) {
            $variant = (array) $this->input('variant', []);
            if ($variant !== []) {
                $variant['stock_quantity'] = 0;
                $this->merge(['variant' => $variant]);
            }
            $variants = (array) $this->input('variants', []);
            foreach ($variants as $i => $v) {
                $variants[$i]['stock_quantity'] = 0;
            }
            if ($variants !== []) {
                $this->merge(['variants' => $variants]);
            }
        }

        // Specifications: flat [group, label, value] rows → grouped ['Group' => ['Label' => 'Value']].
        $grouped = [];
        foreach ((array) $this->input('specs', []) as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $group = trim((string) ($row['group'] ?? '')) ?: 'General';
            $grouped[$group][$label] = (string) ($row['value'] ?? '');
        }
        $this->merge(['specifications' => $grouped ?: null]);

        // Highlights: drop blank bullets.
        $highlights = array_values(array_filter(
            array_map(fn ($h) => trim((string) $h), (array) $this->input('highlights', [])),
            fn ($h) => $h !== '',
        ));
        $this->merge(['highlights' => $highlights ?: null]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        $isVariable = $this->input('variant_mode') === Product::VARIANT_VARIABLE;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'is_stock_tracked' => ['boolean'],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')],
            'unit_id' => ['nullable', Rule::exists('units', 'id')],
            'type' => ['required', Rule::in([Product::TYPE_TRADING, Product::TYPE_MANUFACTURED, Product::TYPE_RAW, Product::TYPE_SERVICE])],
            'variant_mode' => ['required', Rule::in([Product::VARIANT_SIMPLE, Product::VARIANT_VARIABLE])],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'base_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],

            // Product-detail content shown on the storefront product page
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['string', 'max:255'],
            'specs' => ['nullable', 'array'],
            'specs.*.group' => ['nullable', 'string', 'max:100'],
            'specs.*.label' => ['nullable', 'string', 'max:150'],
            'specs.*.value' => ['nullable', 'string', 'max:1000'],
            'specifications' => ['nullable', 'array'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'return_policy' => ['nullable', 'string', 'max:5000'],
            'video_url' => ['nullable', 'url', 'max:255'],

            // Storefront placement
            'is_active' => ['boolean'],
            'is_web_listed' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_trending' => ['boolean'],
            'is_bestseller' => ['boolean'],
            'published_at' => ['nullable', 'date'],

            // Media (ordered; first = primary)
            'images' => ['nullable', 'array'],
            'images.*' => ['integer', Rule::exists('media', 'id')],

            // Simple-mode default variant — pricing & stock (required only when simple)
            'variant' => ['array'],
            'variant.retail_price' => [Rule::requiredIf(! $isVariable), 'numeric', 'min:0', 'max:99999999.99'],
            'variant.cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variant.wholesale_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variant.compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variant.stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'variant.low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'variant.barcode' => ['nullable', 'string', 'max:100'],

            // Variable-mode variant matrix (required only when variable)
            'variant_default' => ['nullable', 'integer', 'min:0'],
            'variants' => [Rule::requiredIf($isVariable), 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.value_ids' => ['required', 'array', 'min:1'],
            'variants.*.value_ids.*' => ['integer', Rule::exists('attribute_values', 'id')],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.retail_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.wholesale_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'variants.*.stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'variants.*.image_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'variants.*.is_active' => ['nullable', 'boolean'],

            // SEO
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'no_index' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'variants.required' => 'Add at least one variant — pick attributes and values, then “Generate variants”.',
            'variants.*.value_ids.required' => 'Each variant needs at least one option selected.',
            'variants.*.retail_price.required' => 'Each variant needs a retail price.',
        ];
    }

    public function attributes(): array
    {
        return [
            'variant.retail_price' => 'retail price',
            'variant.cost' => 'cost',
            'variant.wholesale_price' => 'wholesale price',
            'variant.compare_at_price' => 'compare-at price',
            'variant.stock_quantity' => 'stock quantity',
            'category_id' => 'category',
        ];
    }

    /** Append -2, -3, … until the slug/sku is free (ignoring the row being edited, incl. soft-deleted). */
    private function uniqueValue(string $column, string $base): string
    {
        $base = $base !== '' ? $base : 'product';
        $ignoreId = $this->route('product')?->id;
        $value = $base;
        $i = 2;

        while (
            Product::withTrashed()
                ->where($column, $value)
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $value = "{$base}-{$i}";
            $i++;
        }

        return $value;
    }
}
