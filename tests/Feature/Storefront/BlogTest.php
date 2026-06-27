<?php

namespace Tests\Feature\Storefront;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use DatabaseTransactions;

    private function author(): User
    {
        return User::role('super-admin')->first() ?? User::factory()->create();
    }

    private function makePost(string $title, array $attrs = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'author_id' => $this->author()->id,
            'title' => $title, 'slug' => Str::slug($title) . '-' . uniqid(),
            'body' => '<p>Body of ' . $title . '</p>',
            'status' => 'published', 'published_at' => now()->subDay(),
        ], $attrs));
    }

    public function test_blog_index_renders(): void
    {
        $this->get(route('blog'))->assertOk();
    }

    public function test_published_posts_show_and_drafts_are_hidden(): void
    {
        $this->makePost('Published Insight Article');
        $this->makePost('Hidden Draft Article', ['status' => 'draft', 'published_at' => null]);

        $this->get(route('blog'))
            ->assertOk()
            ->assertSee('Published Insight Article')
            ->assertDontSee('Hidden Draft Article');
    }

    public function test_post_detail_renders_real_post(): void
    {
        $post = $this->makePost('My Detailed Story');

        $this->get(route('blog.show', $post->slug))
            ->assertOk()
            ->assertSee('My Detailed Story')
            ->assertSee('Body of My Detailed Story');
    }

    public function test_unknown_and_draft_posts_404(): void
    {
        $this->get(route('blog.show', 'no-such-post-' . uniqid()))->assertNotFound();

        $draft = $this->makePost('Secret Draft', ['status' => 'draft', 'published_at' => null]);
        $this->get(route('blog.show', $draft->slug))->assertNotFound();
    }

    public function test_category_with_posts_shows_in_the_sidebar(): void
    {
        $category = BlogCategory::create(['name' => 'Spacetech ' . uniqid(), 'slug' => 'st-' . uniqid()]);
        $this->makePost('Categorised Post')->categories()->attach($category->id);

        $this->get(route('blog'))->assertOk()->assertSee($category->name);
    }
}
