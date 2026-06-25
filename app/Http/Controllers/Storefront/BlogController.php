<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\ProvidesSampleBlog;
use App\Http\Controllers\Storefront\Concerns\ProvidesSampleProducts;
use Illuminate\View\View;

class BlogController extends Controller
{
    use ProvidesSampleBlog, ProvidesSampleProducts;

    /**
     * Blog listing. Design-only: replace with BlogPost::published()->latest()
     * ->paginate(per_page()) when the Blog module lands.
     */
    public function index(): View
    {
        $posts = $this->samplePosts();

        return view('storefront.blog.index', [
            'posts' => $posts,
            'categories' => $this->blogCategories(),
            'recentPosts' => $posts->take(3),
            'tags' => $this->blogTags(),
        ]);
    }

    /**
     * Single post. Design-only: replace with
     * BlogPost::where('slug', $slug)->with('author','categories','tags')->firstOrFail().
     */
    public function show(string $slug): View
    {
        $posts = $this->samplePosts();
        $index = $posts->search(fn (array $p): bool => $p['slug'] === $slug);
        if ($index === false) {
            $index = 0;
        }

        $post = $posts[$index];
        $pool = $this->sampleProducts();

        return view('storefront.blog.show', [
            'post' => $post,
            'prev' => $index > 0 ? $posts[$index - 1] : null,
            'next' => $index < $posts->count() - 1 ? $posts[$index + 1] : null,
            'categories' => $this->blogCategories(),
            'recentPosts' => $posts->reject(fn (array $p): bool => $p['slug'] === $post['slug'])->take(2)->values(),
            'tags' => $this->blogTags(),
            'featured' => $pool->take(2)->values(),
            'topSelling' => $pool->slice(10, 1)->values(),
            'onSale' => $pool->whereNotNull('compare')->take(1)->values(),
        ]);
    }
}
