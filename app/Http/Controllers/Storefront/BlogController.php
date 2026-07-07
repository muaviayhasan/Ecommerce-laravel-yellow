<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Support\Storefront;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    private const PLACEHOLDER = 'https://placehold.co/800x500/f1f5f9/94a3b8?text=No+Image';

    /** Blog listing — real published posts, filterable by category / tag / search. */
    public function index(Request $request): View
    {
        $category = trim((string) $request->query('category')) ?: null;
        $tag = trim((string) $request->query('tag')) ?: null;
        $search = trim((string) $request->query('q')) ?: null;

        $posts = BlogPost::published()
            ->with('author:id,name', 'categories:id,name,slug', 'cover')
            ->withCount(['comments' => fn ($q) => $q->where('is_approved', true)])
            ->when($category, fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('slug', $category)))
            ->when($tag, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('slug', $tag)))
            ->when($search, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('title', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%")
                ->orWhere('body', 'like', "%{$search}%")))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $posts->setCollection($posts->getCollection()->map(fn (BlogPost $post) => $this->card($post)));

        return view('storefront.blog.index', [
            'posts' => $posts,
            'categories' => $this->categories(),
            'recentPosts' => $this->recent(),
            'tags' => $this->tags(),
            'activeCategory' => $category,
            'activeTag' => $tag,
            'search' => $search,
            'activeFilter' => $this->activeFilterLabel($category, $tag, $search),
        ]);
    }

    /** Single post by slug (published only). */
    public function show(string $slug): View
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->with('author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'cover')
            ->firstOrFail();

        $comments = $post->comments()
            ->topLevel()
            ->approved()
            ->with(['replies' => fn ($q) => $q->approved()->oldest()])
            ->latest()
            ->get();

        $prev = BlogPost::published()->where('published_at', '<', $post->published_at)->latest('published_at')->first();
        $next = BlogPost::published()->where('published_at', '>', $post->published_at)->oldest('published_at')->first();

        return view('storefront.blog.show', [
            'post' => $this->card($post),
            'comments' => $comments,
            'commentsCount' => $post->comments()->approved()->count(),
            'postCategories' => $post->categories->map(fn (BlogCategory $c) => ['name' => $c->name, 'slug' => $c->slug])->all(),
            'postTags' => $post->tags->map(fn (BlogTag $t) => ['name' => $t->name, 'slug' => $t->slug])->all(),
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
        $plain = strip_tags((string) $post->body);

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
            'excerpt' => $post->excerpt ?: Str::limit($plain, 160),
            'body' => $post->body,
            'reading_time' => max(1, (int) ceil(str_word_count($plain) / 200)),
            'comments' => (int) ($post->comments_count ?? 0),
        ];
    }

    /** Store a visitor comment on a published post (auto-approved). */
    public function storeComment(Request $request, BlogPost $post): RedirectResponse
    {
        abort_unless($post->status === 'published', 404);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ], [
            'body.required' => 'Please write a comment.',
            'body.min' => 'Your comment is a little too short.',
            'website.url' => 'Enter a full URL, e.g. https://example.com',
        ]);

        // On failure, return to the comment section with the errors + entered values.
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->withFragment('comments');
        }

        $data = $validator->validated();

        $post->comments()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'website' => $data['website'] ?? null,
            'body' => $data['body'],
            'is_approved' => true,
        ]);

        return redirect()->to(route('blog.show', $post->slug) . '#comments')
            ->with('comment_status', 'Thanks! Your comment has been posted.');
    }

    /**
     * Categories that have published posts, with slug + count for sidebar links.
     *
     * @return list<array{name:string, slug:string, count:int}>
     */
    private function categories(): array
    {
        return BlogCategory::query()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('name')->get()
            ->filter(fn (BlogCategory $c) => $c->posts_count > 0)
            ->map(fn (BlogCategory $c) => ['name' => $c->name, 'slug' => $c->slug, 'count' => $c->posts_count])
            ->values()->all();
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
     * Tags that have published posts, with slug for sidebar links.
     *
     * @return list<array{name:string, slug:string}>
     */
    private function tags(): array
    {
        return BlogTag::query()
            ->whereHas('posts', fn ($q) => $q->published())
            ->orderBy('name')->get(['id', 'name', 'slug'])
            ->map(fn (BlogTag $t) => ['name' => $t->name, 'slug' => $t->slug])
            ->all();
    }

    /**
     * The active filter (for the listing header + "clear" chip), if any.
     *
     * @return array{type:string, label:string}|null
     */
    private function activeFilterLabel(?string $category, ?string $tag, ?string $search): ?array
    {
        if ($category) {
            return ['type' => 'category', 'label' => BlogCategory::where('slug', $category)->value('name') ?? $category];
        }
        if ($tag) {
            return ['type' => 'tag', 'label' => BlogTag::where('slug', $tag)->value('name') ?? $tag];
        }
        if ($search) {
            return ['type' => 'search', 'label' => $search];
        }

        return null;
    }
}
