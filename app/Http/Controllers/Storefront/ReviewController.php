<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** A logged-in customer submits (or updates) a review — it enters the moderation queue. */
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active && $product->is_sellable, 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $userId = auth()->id();

        $verified = Order::query()
            ->whereHas('customer', fn ($c) => $c->where('user_id', $userId))
            ->whereHas('items.variant', fn ($v) => $v->where('product_id', $product->id))
            ->exists();

        Review::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $userId],
            [
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'body' => $data['body'],
                'is_approved' => false, // back to the moderation queue on every submit/edit
                'verified_purchase' => $verified,
            ],
        );

        return redirect(route('product.show', $product->slug) . '#reviews')
            ->with('review_status', 'Thanks! Your review has been submitted and is awaiting approval.');
    }
}
