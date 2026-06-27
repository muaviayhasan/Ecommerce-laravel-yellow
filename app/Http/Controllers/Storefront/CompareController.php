<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CompareService;
use App\Support\Storefront;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function __construct(private CompareService $compare) {}

    public function index(): View
    {
        return view('storefront.compare', [
            'rows' => $this->compare->products()->map(fn (Product $p) => $this->row($p)),
        ]);
    }

    public function toggle(Product $product): RedirectResponse
    {
        $result = $this->compare->toggle($product->id);

        if ($result['full']) {
            return back()->with('error', 'You can compare up to 4 products — remove one first.');
        }

        return back()->with('status', $result['added'] ? 'Added to compare.' : 'Removed from compare.');
    }

    public function remove(Product $product): RedirectResponse
    {
        $this->compare->remove($product->id);

        return redirect()->route('compare')->with('status', 'Removed from compare.');
    }

    public function clear(): RedirectResponse
    {
        $this->compare->clear();

        return redirect()->route('compare')->with('status', 'Compare list cleared.');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Product $product): array
    {
        $variant = $product->defaultVariant;

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'variant_id' => $variant?->id,
            'name' => $product->name,
            'url' => route('product.show', $product->slug),
            'image' => $product->media->first()?->url ?? $variant?->image?->url ?? Storefront::placeholder(),
            'price' => (float) ($variant?->retail_price ?? 0),
            'category' => $product->category?->name ?? '—',
            'availability' => ($variant && (float) $variant->stock_quantity > 0) ? 'In stock' : 'Out of stock',
            'sku' => $variant?->sku ?? '—',
            'highlights' => array_values((array) ($product->highlights ?? [])),
        ];
    }
}
