<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImportExport\Exporters\CategoryExporter;
use App\Services\ImportExport\ImportException;
use App\Services\ImportExport\Importers\CategoryImporter;
use App\Services\ImportExport\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CategoryImportExportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:categories.export', only: ['export']),
            new Middleware('can:categories.import', only: ['importForm', 'import', 'template']),
        ];
    }

    public function export(Request $request, CategoryExporter $exporter): Response|RedirectResponse
    {
        if ($request->string('format')->toString() === 'pdf') {
            return $exporter->pdf($request)
                ?? back()->with('error', 'PDF export is capped at ' . number_format(CategoryExporter::PDF_ROW_CAP) . ' rows — use the Excel export instead.');
        }

        return $exporter->xlsx($request);
    }

    public function importForm(): View
    {
        return view('admin.import.show', $this->page());
    }

    public function import(Request $request, CategoryImporter $importer): RedirectResponse
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

    public function template(CategoryExporter $exporter): Response
    {
        return $exporter->template();
    }

    private function page(): array
    {
        return [
            'title' => 'Import Categories',
            'entity' => 'categories',
            'indexRoute' => 'admin.categories.index',
            'postRoute' => 'admin.categories.import.store',
            'templateRoute' => 'admin.categories.import.template',
            'maxRows' => SpreadsheetReader::MAX_ROWS,
            'columns' => [
                ['name', 'Yes', 'Category name.'],
                ['slug', 'No', 'Match key. Existing slug updates that category; blank or new slug creates one.'],
                ['parent_slug', 'No', 'Slug of the parent category — may appear later in the same file.'],
                ['description', 'No', 'Up to 5,000 characters.'],
                ['sort_order', 'No', 'Whole number, lower shows first. Default 0.'],
                ['markup_percent', 'No', 'Default markup for pricing, e.g. 12.5.'],
                ['is_active', 'No', '1/0 (also yes/no). Blank keeps the current value.'],
                ['image', 'No', 'Path of an image already in the gallery, e.g. gallery/abc123.webp.'],
                ['meta_title', 'No', 'SEO title, up to 255 characters.'],
                ['meta_description', 'No', 'SEO description, up to 255 characters.'],
            ],
            'notes' => [
                'Rows are matched to existing categories by slug — matching rows update, new rows create.',
                'Blank cells never overwrite existing data; clear a field from the category form instead.',
                'Parents may be defined lower in the same file — they are linked in a second pass.',
                'Nothing is ever deleted by an import.',
            ],
        ];
    }
}
