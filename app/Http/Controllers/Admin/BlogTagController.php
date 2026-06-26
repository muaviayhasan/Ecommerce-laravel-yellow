<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogTagRequest;
use App\Models\BlogTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BlogTagController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:blog-tags.view', only: ['index']),
            new Middleware('can:blog-tags.create', only: ['store']),
            new Middleware('can:blog-tags.edit', only: ['edit', 'update']),
            new Middleware('can:blog-tags.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('admin.blog.tags.index', [
            'tags' => BlogTag::withCount('posts')->orderBy('name')->get(),
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
