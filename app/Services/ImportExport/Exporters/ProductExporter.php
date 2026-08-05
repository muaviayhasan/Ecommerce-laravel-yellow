<?php

namespace App\Services\ImportExport\Exporters;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ImportExport\XlsxResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExporter
{
    /**
     * One spreadsheet row per variant. Product-level columns are read from the
     * first row of each product_sku group on import; they are repeated on every
     * exported row so each row is self-contained for hand editing.
     */
    public const HEADERS = [
        // product columns
        'product_sku', 'name', 'slug', 'category_slug', 'brand_slug', 'unit', 'type', 'variant_mode',
        'short_description', 'description', 'highlights', 'specifications',
        'base_price', 'markup_percent', 'warranty', 'return_policy', 'video_url',
        'length', 'width', 'height',
        'is_active', 'is_featured', 'is_trending', 'is_bestseller', 'is_pinned', 'is_web_listed', 'no_index',
        'is_stock_tracked', 'is_purchasable', 'is_manufacturable', 'is_sellable',
        'published_at', 'meta_title', 'meta_description', 'meta_keywords', 'canonical_url', 'images',
        // variant columns
        'variant_sku', 'attributes', 'cost', 'retail_price', 'wholesale_price', 'compare_at_price',
        'stock_quantity', 'low_stock_threshold', 'weight', 'barcode', 'variant_is_active', 'variant_is_default',
    ];

    public const PDF_ROW_CAP = 2000;

    public function xlsx(Request $request): StreamedResponse
    {
        $rows = function () use ($request) {
            $query = $this->filteredQuery($request)
                ->with([
                    'category:id,slug', 'brand:id,slug', 'unit:id,name',
                    'media:id,disk,path',
                    'variants.attributeValues.attribute:id,code',
                ])
                ->orderBy('id');

            foreach ($query->lazy(200) as $product) {
                $productCells = $this->productCells($product);

                foreach ($product->variants->sortBy('id')->values() as $variant) {
                    yield [...$productCells, ...$this->variantCells($variant)];
                }
            }
        };

        return XlsxResponse::stream('products-' . now()->format('Y-m-d') . '.xlsx', self::HEADERS, $rows());
    }

    public function pdf(Request $request): ?Response
    {
        $query = $this->filteredQuery($request);

        if ($query->count() > self::PDF_ROW_CAP) {
            return null;
        }

        $products = $query->with([
            'category:id,name',
            'variants.attributeValues.attribute:id,code',
        ])->orderBy('id')->get();

        return Pdf::loadView('admin.exports.pdf.products', [
            'products' => $products,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download('products-' . now()->format('Y-m-d') . '.pdf');
    }

    public function template(): StreamedResponse
    {
        $blank = array_fill(0, count(self::HEADERS), '');

        // A simple product and a two-variant variable product, matching the docs on the import page.
        $simple = $blank;
        $simple[0] = 'SKU-1001';           // product_sku
        $simple[1] = 'Room Cooler 55L';    // name
        $simple[3] = 'coolers';            // category_slug
        $simple[6] = 'trading';            // type
        $simple[7] = 'simple';             // variant_mode
        $simple[20] = 1;                   // is_active
        $simple[41] = 24500;               // retail_price
        $simple[44] = 10;                  // stock_quantity

        $varA = $blank;
        $varA[0] = 'SKU-2001';
        $varA[1] = 'Ceiling Fan 56"';
        $varA[3] = 'fans';
        $varA[6] = 'trading';
        $varA[7] = 'variable';
        $varA[20] = 1;
        $varA[37] = 'SKU-2001-WHITE';      // variant_sku
        $varA[38] = 'color=white';         // attributes
        $varA[41] = 8500;
        $varA[44] = 5;
        $varA[48] = 1;                     // variant_is_active
        $varA[49] = 1;                     // variant_is_default

        $varB = $blank;
        $varB[0] = 'SKU-2001';             // same product, second variant row
        $varB[37] = 'SKU-2001-BLACK';
        $varB[38] = 'color=black';
        $varB[41] = 8700;
        $varB[44] = 3;
        $varB[48] = 1;

        return XlsxResponse::stream('products-template.xlsx', self::HEADERS, [$simple, $varA, $varB]);
    }

    private function productCells(Product $p): array
    {
        return [
            $p->sku,
            $p->name,
            $p->slug,
            $p->category?->slug,
            $p->brand?->slug,
            $p->unit?->name,
            $p->type,
            $p->variant_mode,
            $p->short_description,
            $p->description,
            collect($p->highlights ?? [])->implode('|'),
            $p->specifications ? json_encode($p->specifications, JSON_UNESCAPED_UNICODE) : '',
            $p->base_price,
            $p->markup_percent,
            $p->warranty,
            $p->return_policy,
            $p->video_url,
            $p->length,
            $p->width,
            $p->height,
            $p->is_active ? 1 : 0,
            $p->is_featured ? 1 : 0,
            $p->is_trending ? 1 : 0,
            $p->is_bestseller ? 1 : 0,
            $p->is_pinned ? 1 : 0,
            $p->is_web_listed ? 1 : 0,
            $p->no_index ? 1 : 0,
            $p->is_stock_tracked ? 1 : 0,
            $p->is_purchasable ? 1 : 0,
            $p->is_manufacturable ? 1 : 0,
            $p->is_sellable ? 1 : 0,
            $p->published_at?->format('Y-m-d H:i:s'),
            $p->meta_title,
            $p->meta_description,
            $p->meta_keywords,
            $p->canonical_url,
            $p->media->pluck('path')->implode('|'),
        ];
    }

    private function variantCells(ProductVariant $v): array
    {
        return [
            $v->sku,
            self::attributesCell($v),
            $v->cost,
            $v->retail_price,
            $v->wholesale_price,
            $v->compare_at_price,
            $v->stock_quantity,
            $v->low_stock_threshold,
            $v->weight,
            $v->barcode,
            $v->is_active ? 1 : 0,
            $v->is_default ? 1 : 0,
        ];
    }

    /** `code=value|code=value` from the variant's attribute values (import parses the same shape). */
    public static function attributesCell(ProductVariant $v): string
    {
        return $v->attributeValues
            ->sortBy(fn ($av) => $av->attribute?->code)
            ->map(fn ($av) => ($av->attribute?->code ?? '?') . '=' . $av->value)
            ->implode('|');
    }

    /** Mirrors ProductController::index() filters. */
    private function filteredQuery(Request $request)
    {
        return Product::query()
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
            });
    }
}
