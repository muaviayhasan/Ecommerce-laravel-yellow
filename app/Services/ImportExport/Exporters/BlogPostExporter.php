<?php

namespace App\Services\ImportExport\Exporters;

use App\Models\BlogPost;
use App\Services\ImportExport\XlsxResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BlogPostExporter
{
    public const HEADERS = [
        'title', 'slug', 'author', 'status', 'categories', 'published_at',
        'excerpt', 'meta_title', 'meta_description', 'no_index', 'created_at',
    ];

    public const PDF_ROW_CAP = 2000;

    public function xlsx(Request $request): StreamedResponse
    {
        $rows = function () use ($request) {
            foreach ($this->filteredQuery($request)->with('author:id,name', 'categories:id,name')->latest('id')->lazy(500) as $p) {
                yield [
                    $p->title,
                    $p->slug,
                    $p->author?->name,
                    $p->status,
                    $p->categories->pluck('name')->implode('|'),
                    $p->published_at?->format('Y-m-d H:i'),
                    $p->excerpt,
                    $p->meta_title,
                    $p->meta_description,
                    $p->no_index ? 1 : 0,
                    $p->created_at?->format('Y-m-d'),
                ];
            }
        };

        return XlsxResponse::stream('blog-posts-' . now()->format('Y-m-d') . '.xlsx', self::HEADERS, $rows());
    }

    public function pdf(Request $request): ?Response
    {
        $query = $this->filteredQuery($request);

        if ($query->count() > self::PDF_ROW_CAP) {
            return null;
        }

        return Pdf::loadView('admin.exports.pdf.blog-posts', [
            'posts' => $query->with('author:id,name')->withCount('categories')->latest('id')->get(),
            'generatedAt' => now(),
        ])->download('blog-posts-' . now()->format('Y-m-d') . '.pdf');
    }

    /** Mirrors BlogPostController::index() filters. */
    private function filteredQuery(Request $request)
    {
        return BlogPost::query()
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%' . $request->string('search') . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
    }
}
