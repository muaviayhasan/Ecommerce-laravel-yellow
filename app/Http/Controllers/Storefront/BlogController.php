<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Support\Storefront;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    private const PLACEHOLDER = 'https://placehold.co/800x500/f1f5f9/94a3b8?text=No+Image';

    /** Blog listing — real published posts, paginated, with live sidebar facets. */
    public function index(): View
    {
        $posts = BlogPost::published()
            ->with('author:id,name', 'categories:id,name', 'cover')
            ->latest('published_at')
            ->paginate(9);

        $posts->setCollection($posts->getCollection()->map(fn (BlogPost $post) => $this->card($post)));

        return view('storefront.blog.index', [
            'posts' => $posts,
            'categories' => $this->categories(),
            'recentPosts' => $this->recent(),
            'tags' => $this->tags(),
        ]);
    }

    /** Single post by slug (published only). */
    public function show(string $slug): View
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->with('author:id,name', 'categories:id,name', 'cover')
            ->firstOrFail();

        $prev = BlogPost::published()->where('published_at', '<', $post->published_at)->latest('published_at')->first();
        $next = BlogPost::published()->where('published_at', '>', $post->published_at)->oldest('published_at')->first();

        return view('storefront.blog.show', [
            'post' => $this->card($post),
            'prev' => $prev ? ['url' => route('blog.show', $prev->slug), 'title' => $prev->title] : null,
            'next' => $next ? ['url' => route('blog.show', $next->slug), 'title' => $next->title] : null,
            'categories' => $this->categories(),
            'recentPosts' => $this->recent($post->id),
            'tags' => $this->tags(),
            'featured' => Storefront::cards(Storefront::query()->featured()->take(2)->get()),
            'topSelling' => Storefront::cards(Storefront::query()->bestseller()->take(1)->get()),
            'onSale' => Storefront::cards(Storefront::onSaleQuery()->take(1)->get()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function card(BlogPost $post): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'url' => route('blog.show', $post->slug),
            'format' => 'standard',
            'category' => $post->categories->pluck('name')->implode(', ') ?: 'Uncategorized',
            'date' => ($post->published_at ?? $post->created_at)?->format('F j, Y'),
            'published_iso' => ($post->published_at ?? $post->created_at)?->toIso8601String(),
            'updated_iso' => $post->updated_at?->toIso8601String(),
            'author' => $post->author?->name ?? 'admin',
            'image' => $post->cover?->url ?? self::PLACEHOLDER,
            'excerpt' => $post->excerpt ?: Str::limit(strip_tags((string) $post->body), 160),
            'body' => $post->body,
            'comments' => 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function categories(): array
    {
        return BlogCategory::query()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('name')->get()
            ->filter(fn (BlogCategory $c) => $c->posts_count > 0)
            ->mapWithKeys(fn (BlogCategory $c) => [$c->name => $c->posts_count])
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function recent(?int $exceptId = null): Collection
    {
        return BlogPost::published()
            ->with('cover')
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->latest('published_at')->take(3)->get()
            ->map(fn (BlogPost $post) => [
                'url' => route('blog.show', $post->slug),
                'image' => $post->cover?->url ?? self::PLACEHOLDER,
                'title' => $post->title,
                'date' => ($post->published_at ?? $post->created_at)?->format('F j, Y'),
            ]);
    }

    /**
     * @return list<string>
     */
    private function tags(): array
    {
        return BlogTag::query()
            ->whereHas('posts', fn ($q) => $q->published())
            ->orderBy('name')->pluck('name')->all();
    }
}
