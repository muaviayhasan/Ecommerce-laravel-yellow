<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogTagRequest;
use App\Models\BlogTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BlogTagController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:blog-tags.view', only: ['index']),
            new Middleware('can:blog-tags.create', only: ['store']),
            new Middleware('can:blog-tags.edit', only: ['edit', 'update']),
            new Middleware('can:blog-tags.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $tags = BlogTag::query()->withCount('posts');

        $this->applyTableSort($tags, $request, [
            'name' => 'name',
            'slug' => 'slug',
            'posts' => 'posts_count',
        ], fn ($q) => $q->orderBy('name'));

        $perPage = $this->perPageFor($request);
        $tags = $tags->paginate($perPage)->withQueryString();

        return view('admin.blog.tags.index', [
            'tags' => $tags,
            'perPage' => $perPage,
            'filters' => $request->only('sort', 'dir', 'per_page'),
        ]);
    }

    public function store(BlogTagRequest $request): RedirectResponse
    {
        BlogTag::create($request->validated());

        return redirect()->route('admin.blog.tags.index')->with('status', 'Tag added.');
    }

    public function edit(BlogTag $tag): View
    {
        return view('admin.blog.tags.edit', ['tag' => $tag]);
    }

    public function update(BlogTagRequest $request, BlogTag $tag): RedirectResponse
    {
        $tag->update($request->validated());

        return redirect()->route('admin.blog.tags.index')->with('status', 'Tag updated.');
    }

    public function destroy(BlogTag $tag): RedirectResponse
    {
        $tag->posts()->detach();
        $tag->delete();

        return redirect()->route('admin.blog.tags.index')->with('status', 'Tag deleted.');
    }
}
