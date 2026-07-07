<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CategoryController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:categories.view', only: ['index']),
            new Middleware('can:categories.create', only: ['create', 'store']),
            new Middleware('can:categories.edit', only: ['edit', 'update']),
            new Middleware('can:categories.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $categories = Category::query()
            ->with(['parent:id,name', 'image:id,disk,path'])
            ->withCount('products')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('slug', 'like', $term));
            })
            ->when($request->filled('parent'), fn ($q) => $q->where('parent_id', $request->integer('parent')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'));

        $this->applyTableSort($categories, $request, [
            'name' => 'name',
            'products' => 'products_count',
            'sort' => 'sort_order',
            'status' => 'is_active',
        ], fn ($q) => $q->orderBy('sort_order')->orderBy('name'));

        $perPage = $this->perPageFor($request);
        $categories = $categories->paginate($perPage)->withQueryString();

        return view('admin.categories.index', [
            'categories' => $categories,
            'parents' => Category::orderBy('name')->pluck('name', 'id'),
            'perPage' => $perPage,
            'stats' => [
                'total' => Category::count(),
                'active' => Category::where('is_active', true)->count(),
                'roots' => Category::whereNull('parent_id')->count(),
            ],
            'filters' => $request->only('search', 'parent', 'status', 'sort', 'dir', 'per_page'),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'category' => new Category(['is_active' => true, 'sort_order' => 0]),
            'parentOptions' => $this->parentOptions(),
            'mediaItems' => $this->mediaItems(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parentOptions' => $this->parentOptions($category),
            'mediaItems' => $this->mediaItems(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete a category that still has products. Move them first.');
        }

        // Children are detached (parent_id → null via the FK) before removal.
        $category->children()->update(['parent_id' => null]);
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category deleted.');
    }

    /** Parent dropdown options, excluding the category being edited (no self-parenting). */
    private function parentOptions(?Category $except = null): array
    {
        return Category::query()
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Existing media for the visual image pickers.
     *
     * @return \Illuminate\Support\Collection<int, array{id:int, url:string, title:string}>
     */
    private function mediaItems(): \Illuminate\Support\Collection
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
