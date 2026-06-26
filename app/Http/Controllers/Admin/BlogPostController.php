<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogPostRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BlogPostController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:blog-posts.view', only: ['index']),
            new Middleware('can:blog-posts.create', only: ['create', 'store']),
            new Middleware('can:blog-posts.edit', only: ['edit', 'update']),
            new Middleware('can:blog-posts.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $posts = BlogPost::query()
            ->with('author:id,name', 'cover:id,disk,path')
            ->withCount('categories')
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%' . $request->string('search') . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(per_page())
            ->withQueryString();

        return view('admin.blog.posts.index', [
            'posts' => $posts,
            'filters' => $request->only('search', 'status'),
            'stats' => [
                'total' => BlogPost::count(),
                'published' => BlogPost::where('status', 'published')->count(),
                'drafts' => BlogPost::where('status', 'draft')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.blog.posts.create', [
            'post' => new BlogPost(['status' => 'draft']),
            'selectedCategories' => [],
            'selectedTags' => [],
        ] + $this->formData());
    }

    public function store(BlogPostRequest $request): RedirectResponse
    {
        $data = $this->prepared($request);

        $post = BlogPost::create($data['attributes'] + ['author_id' => auth()->id()]);
        $post->categories()->sync($data['categories']);
        $post->tags()->sync($data['tags']);

        return redirect()->route('admin.blog.posts.index')->with('status', 'Post saved.');
    }

    public function edit(BlogPost $post): View
    {
        return view('admin.blog.posts.edit', [
            'post' => $post,
            'selectedCategories' => $post->categories()->pluck('blog_categories.id')->all(),
            'selectedTags' => $post->tags()->pluck('blog_tags.id')->all(),
        ] + $this->formData());
    }

    public function update(BlogPostRequest $request, BlogPost $post): RedirectResponse
    {
        $data = $this->prepared($request);

        $post->update($data['attributes']);
        $post->categories()->sync($data['categories']);
        $post->tags()->sync($data['tags']);

        return redirect()->route('admin.blog.posts.index')->with('status', 'Post updated.');
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.blog.posts.index')->with('status', 'Post deleted.');
    }

    /**
     * Split the validated payload into post attributes + the pivot ids, and
     * stamp published_at the moment a post first goes live without an explicit date.
     *
     * @return array{attributes: array<string, mixed>, categories: array<int, int>, tags: array<int, int>}
     */
    private function prepared(BlogPostRequest $request): array
    {
        $data = $request->validated();
        $categories = $data['categories'] ?? [];
        $tags = $data['tags'] ?? [];
        unset($data['categories'], $data['tags']);

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return ['attributes' => $data, 'categories' => $categories, 'tags' => $tags];
    }

    /**
     * @return array{categories: \Illuminate\Support\Collection<int, BlogCategory>, tags: \Illuminate\Support\Collection<int, BlogTag>, mediaItems: Collection<int, array{id:int, url:string, title:string}>}
     */
    private function formData(): array
    {
        return [
            'categories' => BlogCategory::orderBy('name')->get(['id', 'name']),
            'tags' => BlogTag::orderBy('name')->get(['id', 'name']),
            'mediaItems' => $this->mediaItems(),
        ];
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
