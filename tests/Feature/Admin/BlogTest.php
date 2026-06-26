<?php

namespace Tests\Feature\Admin;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.blog.posts.index'))->assertForbidden();
    }

    public function test_admin_can_view_posts_index(): void
    {
        $this->actingAs($this->admin())->get(route('admin.blog.posts.index'))
            ->assertOk()->assertSee('Blog posts');
    }

    public function test_publishing_a_post_syncs_taxonomy_and_stamps_the_date(): void
    {
        $category = BlogCategory::create(['name' => 'News ' . uniqid(), 'slug' => 'news-' . uniqid()]);
        $tag = BlogTag::create(['name' => 'Hot ' . uniqid(), 'slug' => 'hot-' . uniqid()]);

        $this->actingAs($this->admin())->post(route('admin.blog.posts.store'), [
            'title' => 'My First Post', 'slug' => '', 'body' => '<p>Hello world</p>',
            'status' => 'published', 'categories' => [$category->id], 'tags' => [$tag->id],
        ])->assertRedirect(route('admin.blog.posts.index'));

        $post = BlogPost::where('title', 'My First Post')->firstOrFail();
        $this->assertSame('my-first-post', $post->slug);
        $this->assertNotNull($post->published_at);
        $this->assertSame($this->admin()->id, $post->author_id);
        $this->assertEqualsCanonicalizing([$category->id], $post->categories->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$tag->id], $post->tags->pluck('id')->all());
    }

    public function test_a_draft_post_has_no_publish_date(): void
    {
        $this->actingAs($this->admin())->post(route('admin.blog.posts.store'), [
            'title' => 'Draft Post ' . uniqid(), 'body' => 'x', 'status' => 'draft',
        ])->assertRedirect();

        $this->assertNull(BlogPost::latest('id')->first()->published_at);
    }

    public function test_admin_can_add_a_blog_category_and_tag(): void
    {
        $catName = 'Tech ' . uniqid();
        $tagName = 'Laravel ' . uniqid();

        $this->actingAs($this->admin())->post(route('admin.blog.categories.store'), ['name' => $catName])
            ->assertRedirect(route('admin.blog.categories.index'));
        $this->actingAs($this->admin())->post(route('admin.blog.tags.store'), ['name' => $tagName])
            ->assertRedirect(route('admin.blog.tags.index'));

        $this->assertDatabaseHas('blog_categories', ['name' => $catName]);
        $this->assertDatabaseHas('blog_tags', ['name' => $tagName]);
    }

    public function test_post_can_be_deleted(): void
    {
        $post = BlogPost::create([
            'author_id' => $this->admin()->id, 'title' => 'Bye ' . uniqid(), 'slug' => 'bye-' . uniqid(),
            'body' => 'x', 'status' => 'draft',
        ]);

        $this->actingAs($this->admin())->delete(route('admin.blog.posts.destroy', $post))->assertRedirect();
        $this->assertSoftDeleted('blog_posts', ['id' => $post->id]);
    }
}
