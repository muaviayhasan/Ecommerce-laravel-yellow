<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
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

    /** Shop / catalog listing — one card per active variant, with category, brand, price, search, sort and pagination. */
    public function index(Request $request): View
    {
        $query = Storefront::variantQuery();

        if ($request->filled('q')) {
            $query->whereHas('product', fn ($p) => $p->where('name', 'like', '%' . $request->string('q') . '%'));
        }
        // Category / brand accept a single slug (?category=x) or many (?category[]=x&category[]=y).
        // A parent slug also matches everything in its sub-tree (e.g. "coolers" →
        // Air Cooler + Water Cooler), so department links are never empty.
        if ($categories = array_filter((array) $request->input('category', []))) {
            $slugs = $this->categorySlugsWithDescendants($categories);
            $query->whereHas('product.category', fn ($c) => $c->whereIn('slug', $slugs));
        }
        if ($brandSlugs = array_filter((array) $request->input('brand', []))) {
            $query->whereHas('product.brand', fn ($b) => $b->whereIn('slug', $brandSlugs));
        }
        if ($request->filled('min')) {
            $query->where('retail_price', '>=', (float) $request->input('min'));
        }
        if ($request->filled('max')) {
            $query->where('retail_price', '<=', (float) $request->input('max'));
        }

        $this->applySort($query, $request->string('sort')->toString());

        $paginator = $query->paginate(12)->withQueryString();
        $paginator->setCollection(Storefront::variantCards($paginator->getCollection()));

        // Mobile infinite scroll fetches subsequent pages as a lightweight items partial.
        if ($request->boolean('partial')) {
            return view('storefront.partials.shop-items', ['products' => $paginator]);
        }

        // When exactly one category is filtered, surface it so the shop page can
        // emit category-specific SEO (title, meta description, canonical).
        $catSlugs = array_values($categories ?? []);
        $activeCategory = count($catSlugs) === 1
            ? Category::query()->where('is_active', true)->where('slug', $catSlugs[0])->first()
            : null;

        return view('storefront.shop', [
            'products' => $paginator,
            'activeCategory' => $activeCategory,
            'recommended' => Storefront::cards(Storefront::query()->latest('published_at')->take(8)->get()),
            'latest' => Storefront::cards(Storefront::query()->latest('published_at')->take(3)->get()),
            'categories' => Category::query()->where('is_active', true)->whereNull('parent_id')
                ->with(['children' => fn ($c) => $c->where('is_active', true)->orderBy('name')])
                ->withCount(['products' => fn ($q) => $q->webListed()])
                ->orderBy('name')->get(),
            'brands' => Brand::query()->where('is_active', true)
                ->withCount(['products' => fn ($q) => $q->webListed()])
                ->orderBy('name')->get()->where('products_count', '>', 0)->values(),
            'filters' => [
                'q' => $request->input('q'),
                'category' => array_values(array_filter((array) $request->input('category', []))),
                'brand' => array_values(array_filter((array) $request->input('brand', []))),
                'min' => $request->input('min'),
                'max' => $request->input('max'),
                'sort' => $request->input('sort'),
            ],
            'sorts' => self::SORTS,
        ]);
    }

    /**
     * Expand the requested category slugs to include every descendant slug, so a
     * parent department (e.g. "geysers") also lists products filed under its
     * children (Instant / Electric / Gas Geysers). Walks the tree level by level.
     *
     * @param  list<string>  $slugs
     * @return list<string>
     */
    private function categorySlugsWithDescendants(array $slugs): array
    {
        $matched = Category::whereIn('slug', $slugs)->get(['id', 'slug']);
        $out = $matched->pluck('slug')->all();
        $frontier = $matched->pluck('id');

        while ($frontier->isNotEmpty()) {
            $children = Category::whereIn('parent_id', $frontier)->get(['id', 'slug']);
            if ($children->isEmpty()) {
                break;
            }
            $out = array_merge($out, $children->pluck('slug')->all());
            $frontier = $children->pluck('id');
        }

        return array_values(array_unique($out));
    }

    /** Sort the per-variant query. Name/popularity/newness sort by the parent product. */
    private function applySort($query, string $sort): void
    {
        $productCol = fn (string $col) => \App\Models\Product::select($col)
            ->whereColumn('products.id', 'product_variants.product_id')
            ->limit(1);

        $reviewCount = \App\Models\Review::selectRaw('count(*)')
            ->whereColumn('reviews.product_id', 'product_variants.product_id');

        match ($sort) {
            'price_low' => $query->orderBy('retail_price'),
            'price_high' => $query->orderByDesc('retail_price'),
            'name' => $query->orderBy($productCol('name'))->orderBy('product_variants.id'),
            'popular' => $query->orderByDesc($reviewCount)->orderBy('product_variants.id'),
            default => $query->orderByDesc($productCol('published_at'))->orderBy('product_variants.id'),
        };
    }
}
