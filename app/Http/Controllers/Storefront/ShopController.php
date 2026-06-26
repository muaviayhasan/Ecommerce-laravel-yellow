<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Support\Storefront;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    private const SORTS = [
        'newness' => 'Newest first',
        'price_low' => 'Price: low to high',
        'price_high' => 'Price: high to low',
        'name' => 'Name: A to Z',
        'popular' => 'Popularity',
    ];

    /** Shop / catalog listing — real products with category, brand, price, search, sort and pagination. */
    public function index(Request $request): View
    {
        $query = Storefront::query();

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->string('q') . '%');
        }
        if ($request->filled('category')) {
            $query->whereHas('category', fn ($c) => $c->where('slug', $request->string('category')));
        }
        if ($request->filled('brand')) {
            $query->whereHas('brand', fn ($b) => $b->where('slug', $request->string('brand')));
        }
        if ($request->filled('min')) {
            $query->whereHas('defaultVariant', fn ($v) => $v->where('retail_price', '>=', (float) $request->input('min')));
        }
        if ($request->filled('max')) {
            $query->whereHas('defaultVariant', fn ($v) => $v->where('retail_price', '<=', (float) $request->input('max')));
        }

        $this->applySort($query, $request->string('sort')->toString());

        $paginator = $query->paginate(12)->withQueryString();
        $paginator->setCollection(Storefront::cards($paginator->getCollection()));

        return view('storefront.shop', [
            'products' => $paginator,
            'recommended' => Storefront::cards(Storefront::query()->latest('published_at')->take(8)->get()),
            'latest' => Storefront::cards(Storefront::query()->latest('published_at')->take(3)->get()),
            'featured' => Storefront::cards(Storefront::query()->featured()->take(2)->get()),
            'topSelling' => Storefront::cards(Storefront::query()->bestseller()->take(2)->get()),
            'onSale' => Storefront::cards(Storefront::onSaleQuery()->take(1)->get()),
            'categories' => Category::query()->where('is_active', true)->whereNull('parent_id')
                ->with(['children' => fn ($c) => $c->where('is_active', true)->orderBy('name')])
                ->withCount(['products' => fn ($q) => $q->webListed()])
                ->orderBy('name')->get(),
            'brands' => Brand::query()->where('is_active', true)
                ->withCount(['products' => fn ($q) => $q->webListed()])
                ->orderBy('name')->get()->where('products_count', '>', 0)->values(),
            'filters' => $request->only('q', 'category', 'brand', 'min', 'max', 'sort'),
            'sorts' => self::SORTS,
        ]);
    }

    private function applySort($query, string $sort): void
    {
        $defaultPrice = ProductVariant::select('retail_price')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->where('is_default', true)
            ->limit(1);

        match ($sort) {
            'price_low' => $query->orderBy((clone $defaultPrice), 'asc'),
            'price_high' => $query->orderBy((clone $defaultPrice), 'desc'),
            'name' => $query->orderBy('name'),
            'popular' => $query->withCount('reviews')->orderByDesc('reviews_count'),
            default => $query->latest('published_at'),
        };
    }
}
