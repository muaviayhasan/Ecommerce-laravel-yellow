<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogCategoryRequest;
use App\Models\BlogCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlogCategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:blog-categories.view', only: ['index']),
            new Middleware('can:blog-categories.create', only: ['store']),
            new Middleware('can:blog-categories.edit', only: ['edit', 'update', 'reorder']),
            new Middleware('can:blog-categories.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('admin.blog.categories.index', [
            'categories' => BlogCategory::with('parent:id,name')->withCount('posts')->orderBy('sort_order')->orderBy('name')->get(),
            'parents' => BlogCategory::orderBy('name')->pluck('name', 'id'),
            'nextSort' => (int) BlogCategory::max('sort_order') + 1,
        ]);
    }

    public function store(BlogCategoryRequest $request): RedirectResponse
    {
        BlogCategory::create($request->validated());

        return redirect()->route('admin.blog.categories.index')->with('status', 'Category added.');
    }

    /** Persist a new drag-and-drop order: each id's position becomes its sort_order. */
    public function reorder(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('blog_categories', 'id')],
        ])['ids'];

        foreach (array_values($ids) as $position => $id) {
            BlogCategory::whereKey($id)->update(['sort_order' => $position + 1]);
        }

        return response()->json(['ok' => true]);
    }

    public function edit(BlogCategory $category): View
    {
        return view('admin.blog.categories.edit', [
            'category' => $category,
            'parents' => BlogCategory::whereKeyNot($category->id)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(BlogCategoryRequest $request, BlogCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.blog.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(BlogCategory $category): RedirectResponse
    {
        $category->children()->update(['parent_id' => null]);
        $category->posts()->detach();
        $category->delete();

        return redirect()->route('admin.blog.categories.index')->with('status', 'Category deleted.');
    }
}
