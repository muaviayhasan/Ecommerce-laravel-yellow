<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Models\Brand;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BrandController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:brands.view', only: ['index']),
            new Middleware('can:brands.create', only: ['create', 'store']),
            new Middleware('can:brands.edit', only: ['edit', 'update']),
            new Middleware('can:brands.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $brands = Brand::query()
            ->with('logo:id,disk,path')
            ->withCount('products')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('slug', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'))
            ->orderBy('name')
            ->paginate(per_page())
            ->withQueryString();

        return view('admin.brands.index', [
            'brands' => $brands,
            'stats' => [
                'total' => Brand::count(),
                'active' => Brand::where('is_active', true)->count(),
            ],
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.brands.create', [
            'brand' => new Brand(['is_active' => true]),
            'mediaItems' => $this->mediaItems(),
        ]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        Brand::create($request->validated());

        return redirect()->route('admin.brands.index')->with('status', 'Brand created.');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.edit', [
            'brand' => $brand,
            'mediaItems' => $this->mediaItems(),
        ]);
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        return redirect()->route('admin.brands.index')->with('status', 'Brand updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return back()->with('error', 'Cannot delete a brand that still has products. Move them first.');
        }

        $brand->delete();

        return redirect()->route('admin.brands.index')->with('status', 'Brand deleted.');
    }

    /**
     * @return Collection<int, array{id:int, url:string, title:string}>
     */
    private function mediaItems(): Collection
    {
        return Media::query()
            ->latest('id')
            ->limit(200)
            ->get(['id', 'disk', 'path', 'title'])
            ->map(fn (Media $m) => [
                'id' => $m->id,
                'url' => $m->url,
                'title' => $m->title ?: basename($m->path),
            ]);
    }
}
