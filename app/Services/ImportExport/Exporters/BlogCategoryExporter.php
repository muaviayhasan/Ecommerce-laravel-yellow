<?php

namespace App\Services\ImportExport\Exporters;

use App\Models\BlogCategory;
use App\Services\ImportExport\XlsxResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BlogCategoryExporter
{
    public const HEADERS = ['name', 'slug', 'parent', 'posts', 'sort_order'];

    public const PDF_ROW_CAP = 2000;

    public function xlsx(Request $request): StreamedResponse
    {
        $rows = function () {
            foreach ($this->query()->lazy(500) as $c) {
                yield [$c->name, $c->slug, $c->parent?->name, $c->posts_count, $c->sort_order];
            }
        };

        return XlsxResponse::stream('blog-categories-' . now()->format('Y-m-d') . '.xlsx', self::HEADERS, $rows());
    }

    public function pdf(Request $request): ?Response
    {
        if ($this->query()->count() > self::PDF_ROW_CAP) {
            return null;
        }

        return Pdf::loadView('admin.exports.pdf.blog-categories', [
            'categories' => $this->query()->get(),
            'generatedAt' => now(),
        ])->download('blog-categories-' . now()->format('Y-m-d') . '.pdf');
    }

    /** The blog-categories index has no filters — same ordered list. */
    private function query()
    {
        return BlogCategory::with('parent:id,name')->withCount('posts')->orderBy('sort_order')->orderBy('name');
    }
}
