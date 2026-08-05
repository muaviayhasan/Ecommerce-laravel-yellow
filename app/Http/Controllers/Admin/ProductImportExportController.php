<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImportExport\Exporters\ProductExporter;
use App\Services\ImportExport\ImportException;
use App\Services\ImportExport\Importers\ProductImporter;
use App\Services\ImportExport\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProductImportExportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:products.export', only: ['export']),
            new Middleware('can:products.import', only: ['importForm', 'import', 'template']),
        ];
    }

    public function export(Request $request, ProductExporter $exporter): Response|RedirectResponse
    {
        if ($request->string('format')->toString() === 'pdf') {
            return $exporter->pdf($request)
                ?? back()->with('error', 'PDF export is capped at ' . number_format(ProductExporter::PDF_ROW_CAP) . ' rows — use the Excel export instead.');
        }

        return $exporter->xlsx($request);
    }

    public function importForm(): View
    {
        return view('admin.import.show', $this->page());
    }

    public function import(Request $request, ProductImporter $importer): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:10240']]);

        try {
            $result = $importer->import($request->file('file')->getRealPath());
        } catch (ImportException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()
            ->with('status', "Import finished — {$result->created} created, {$result->updated} updated" . ($result->failed ? ", {$result->failed} failed" : '') . '.')
            ->with('import_result', $result->toArray());
    }

    public function template(ProductExporter $exporter): Response
    {
        return $exporter->template();
    }

    private function page(): array
    {
        return [
            'title' => 'Import Products',
            'entity' => 'products',
            'indexRoute' => 'admin.products.index',
            'postRoute' => 'admin.products.import.store',
            'templateRoute' => 'admin.products.import.template',
            'maxRows' => SpreadsheetReader::MAX_ROWS,
            'columns' => [
                ['product_sku', 'Yes', 'Match key, required on every row. Rows sharing a product_sku form one product; its details are read from the first of those rows.'],
                ['name', 'On create', 'Product name.'],
                ['slug', 'No', 'Web address slug — generated from the name when blank.'],
                ['category_slug', 'On create', 'Slug of an existing category.'],
                ['brand_slug / unit', 'No', 'Slug of an existing brand / name of an existing unit.'],
                ['type', 'No', 'trading, manufactured, raw or service (default trading).'],
                ['variant_mode', 'No', 'simple (one row) or variable (one row per variant, default simple).'],
                ['highlights', 'No', 'Pipe-separated bullets, e.g. Fast cooling|1-year warranty.'],
                ['specifications', 'No', 'JSON like {"General":{"Color":"White"}}.'],
                ['flags (is_active, is_web_listed, …)', 'No', '1/0 (also yes/no). Blank keeps the current value.'],
                ['published_at', 'No', 'Y-m-d H:i:s — blank on create leaves the product a draft.'],
                ['images', 'No', 'Pipe-separated gallery paths, e.g. gallery/a.webp|gallery/b.webp. First image becomes primary; blank leaves the gallery unchanged.'],
                ['variant_sku', 'No', 'Match key for the variant. Blank generates one.'],
                ['attributes', 'Variable rows', 'code=value pairs, e.g. color=white|size=56. Attribute codes must exist; new values are created automatically.'],
                ['cost / retail_price / wholesale_price / compare_at_price', 'No', 'Money values. Blank keeps the current value (new variants: 0 / empty).'],
                ['stock_quantity / low_stock_threshold', 'No', 'Numbers. Blank keeps the current value.'],
                ['variant_is_active / variant_is_default', 'No', '1/0. One row per product may set variant_is_default=1.'],
            ],
            'notes' => [
                'Products match by product_sku, variants by variant_sku — matches update, new ones are created.',
                'Blank cells never overwrite existing data; clear fields from the product form instead.',
                'Variants missing from the file are left untouched — an import never deletes variants (switching a product to simple mode collapses it to one variant, since that is what simple means).',
                'The easiest workflow: export products, edit prices/stock in Excel, and re-import the same file.',
                'A problem in any row of a product rolls back that whole product and continues with the next one.',
            ],
        ];
    }
}
