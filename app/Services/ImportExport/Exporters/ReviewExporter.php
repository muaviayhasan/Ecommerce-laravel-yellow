<?php

namespace App\Services\ImportExport\Exporters;

use App\Models\Review;
use App\Services\ImportExport\XlsxResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReviewExporter
{
    public const HEADERS = [
        'product', 'reviewer', 'rating', 'title', 'body',
        'verified_purchase', 'approved', 'helpful_count', 'date',
    ];

    public const PDF_ROW_CAP = 2000;

    public function xlsx(Request $request): StreamedResponse
    {
        $rows = function () use ($request) {
            foreach ($this->filteredQuery($request)->with('product:id,name', 'user:id,name')->latest('id')->lazy(500) as $r) {
                yield [
                    $r->product?->name,
                    $r->user?->name ?? 'Customer',
                    $r->rating,
                    $r->title,
                    $r->body,
                    $r->verified_purchase ? 1 : 0,
                    $r->is_approved ? 1 : 0,
                    $r->helpful_count,
                    $r->created_at?->format('Y-m-d'),
                ];
            }
        };

        return XlsxResponse::stream('reviews-' . now()->format('Y-m-d') . '.xlsx', self::HEADERS, $rows());
    }

    public function pdf(Request $request): ?Response
    {
        $query = $this->filteredQuery($request);

        if ($query->count() > self::PDF_ROW_CAP) {
            return null;
        }

        return Pdf::loadView('admin.exports.pdf.reviews', [
            'reviews' => $query->with('product:id,name', 'user:id,name')->latest('id')->get(),
            'generatedAt' => now(),
        ])->download('reviews-' . now()->format('Y-m-d') . '.pdf');
    }

    /** Mirrors ReviewController::index() filters. */
    private function filteredQuery(Request $request)
    {
        return Review::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('body', 'like', $term)
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term))
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)));
            })
            ->when($request->filled('rating'), fn ($q) => $q->where('rating', (int) $request->integer('rating')))
            ->when($request->input('status') === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($request->input('status') === 'approved', fn ($q) => $q->where('is_approved', true));
    }
}
