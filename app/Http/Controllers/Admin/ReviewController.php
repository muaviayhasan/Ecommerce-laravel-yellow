<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ReviewController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:reviews.view', only: ['index']),
            new Middleware('can:reviews.moderate', only: ['approve', 'unapprove', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $reviews = Review::query()
            ->with('product:id,name,slug', 'user:id,name')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('body', 'like', $term)
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term))
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)));
            })
            ->when($request->filled('rating'), fn ($q) => $q->where('rating', (int) $request->integer('rating')))
            ->when($request->input('status') === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($request->input('status') === 'approved', fn ($q) => $q->where('is_approved', true));

        $this->applyTableSort($reviews, $request, [
            'oldest' => fn ($q) => $q->orderBy('id'),
            'rating_high' => fn ($q) => $q->orderByDesc('rating')->latest('id'),
            'rating_low' => fn ($q) => $q->orderBy('rating')->latest('id'),
        ], fn ($q) => $q->latest('id'));

        $perPage = $this->perPageFor($request);
        $reviews = $reviews->paginate($perPage)->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'filters' => $request->only('search', 'status', 'rating', 'sort', 'per_page'),
            'perPage' => $perPage,
            'stats' => [
                'total' => Review::count(),
                'pending' => Review::where('is_approved', false)->count(),
                'approved' => Review::where('is_approved', true)->count(),
                'avg' => round((float) Review::avg('rating'), 1),
            ],
        ]);
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update(['is_approved' => true]);

        return back()->with('status', 'Review approved — it is now visible on the storefront.');
    }

    public function unapprove(Review $review): RedirectResponse
    {
        $review->update(['is_approved' => false]);

        return back()->with('status', 'Review moved back to pending.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('status', 'Review deleted.');
    }
}
