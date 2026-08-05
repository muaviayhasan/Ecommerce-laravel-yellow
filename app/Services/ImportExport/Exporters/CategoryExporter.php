<?php

namespace App\Services\ImportExport\Exporters;

use App\Models\Category;
use App\Services\ImportExport\XlsxResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryExporter
{
    /** Shared with the importer so exports round-trip. */
    public const HEADERS = [
        'name', 'slug', 'parent_slug', 'description', 'sort_order',
        'markup_percent', 'is_active', 'image', 'meta_title', 'meta_description',
    ];

    public const PDF_ROW_CAP = 2000;

    public function xlsx(Request $request): StreamedResponse
    {
        $rows = function () use ($request) {
            // Parents before children so the file re-imports in one pass.
            foreach ($this->filteredQuery($request)
                ->with(['parent:id,slug', 'image:id,path'])
                ->orderByRaw('parent_id IS NOT NULL')->orderBy('sort_order')->orderBy('name')
                ->lazy(500) as $c) {
                yield [
                    $c->name,
                    $c->slug,
                    $c->parent?->slug,
                    $c->description,
                    $c->sort_order,
                    $c->markup_percent,
                    $c->is_active ? 1 : 0,
                    $c->image?->path,
                    $c->meta_title,
                    $c->meta_description,
                ];
            }
        };

        return XlsxResponse::stream('categories-' . now()->format('Y-m-d') . '.xlsx', self::HEADERS, $rows());
    }

    /** Null when over the PDF cap — the controller flashes the "use Excel" error. */
    public function pdf(Request $request): ?Response
    {
        $query = $this->filteredQuery($request);

        if ($query->count() > self::PDF_ROW_CAP) {
            return null;
        }

        $categories = $query->with('parent:id,name')
            ->orderByRaw('parent_id IS NOT NULL')->orderBy('sort_order')->orderBy('name')
            ->get();

        return Pdf::loadView('admin.exports.pdf.categories', [
            'categories' => $categories,
            'generatedAt' => now(),
        ])->download('categories-' . now()->format('Y-m-d') . '.pdf');
    }

    public function template(): StreamedResponse
    {
        return XlsxResponse::stream('categories-template.xlsx', self::HEADERS, [
            ['Electronics', 'electronics', '', 'Everything electric', 0, '', 1, '', '', ''],
            ['Coolers', 'coolers', 'electronics', '', 1, '12.5', 1, '', 'Room Coolers', 'Best coolers in town'],
        ]);
    }

    /** Mirrors CategoryController::index() filters so exports match what the admin sees. */
    private function filteredQuery(Request $request)
    {
        return Category::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('slug', 'like', $term));
            })
            ->when($request->filled('parent'), fn ($q) => $q->where('parent_id', $request->integer('parent')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->toString() === 'active'));
    }
}
