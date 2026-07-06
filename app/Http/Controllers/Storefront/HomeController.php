<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Models\InfoBarItem;
use App\Models\PromoCard;
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
            'heroSlides' => $this->heroSlides(),
            'promoCards' => PromoCard::query()->with('image')->active()->ordered()->get(),
            'infoBarItems' => InfoBarItem::query()->active()->ordered()->get(),
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
     * Active hero carousel slides in display order. Falls back to a single built-in
     * slide so the banner is never empty on a fresh install (before HeroSlideSeeder).
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\HeroSlide>
     */
    private function heroSlides(): Collection
    {
        $slides = HeroSlide::query()->with('image')->active()->ordered()->get();

        if ($slides->isNotEmpty()) {
            return $slides;
        }

        return collect([new HeroSlide([
            'kicker' => 'Welcome',
            'line1' => 'SHOP THE LATEST',
            'line2' => 'HOME APPLIANCES',
            'tail' => 'DEALS',
            'highlight' => 'EVERY DAY',
            'cta_label' => 'Shop Now',
            'image_path' => '/assets/images/banner-laptops.png',
            'image_alt' => 'Featured products',
        ])]);
    }

    /**
     * Generic "coming soon" placeholder for storefront pages not yet built.
     */
    public function placeholder(string $title): View
    {
        return view('storefront.placeholder', ['pageTitle' => $title]);
    }
}
