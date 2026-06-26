<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Storefront;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Storefront home page — real catalog (Product::webListed()), mapped to the
     * card shape the theme expects. Sections fall back to the latest products so a
     * thin catalog still fills the page even before the flags are set widely.
     */
    public function index(): View
    {
        $latest = Storefront::cards(Storefront::query()->latest('published_at')->take(12)->get());

        $orLatest = fn (Collection $cards, int $n): Collection => $cards->isEmpty() ? $latest->take($n)->values() : $cards;

        $featured = $orLatest(Storefront::cards(Storefront::query()->featured()->latest('published_at')->take(6)->get()), 6);
        $trending = $orLatest(Storefront::cards(Storefront::query()->trending()->latest('published_at')->take(8)->get()), 8);
        $bestsellers = $orLatest(Storefront::cards(Storefront::query()->bestseller()->latest('published_at')->take(8)->get()), 8);
        $onSale = Storefront::cards(Storefront::onSaleQuery()->take(6)->get());
        $topRated = $orLatest(Storefront::cards(Storefront::query()->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating')->take(6)->get()), 6);

        return view('storefront.home', [
            'featured' => $featured,
            'onSale' => $onSale,
            'topRated' => $topRated,
            'laptops' => $latest,
            'trending' => $trending,
            'bestsellers' => $bestsellers,
            'bestsellerFeature' => $bestsellers->first() ?? $latest->first() ?? [],
            'tvProducts' => $latest,
            'recentlyViewed' => $latest->slice(2, 6)->values(),
        ]);
    }

    /**
     * Generic "coming soon" placeholder for storefront pages not yet built.
     */
    public function placeholder(string $title): View
    {
        return view('storefront.placeholder', ['pageTitle' => $title]);
    }
}
