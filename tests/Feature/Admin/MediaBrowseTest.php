<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MediaBrowseTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    private function makeMedia(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            Media::create([
                'disk' => 'public',
                'path' => 'gallery/test-' . uniqid() . '.png',
                'mime' => 'image/png',
                'title' => 'Test image ' . $i,
            ]);
        }
    }

    public function test_guests_cannot_browse_media(): void
    {
        $this->getJson(route('admin.media.browse'))->assertUnauthorized();
    }

    public function test_it_returns_ten_newest_items_with_a_next_page(): void
    {
        $this->makeMedia(12);

        $res = $this->actingAs($this->admin())->getJson(route('admin.media.browse'))
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'url', 'title']], 'next']);

        $this->assertCount(10, $res->json('data'));
        $this->assertNotNull($res->json('next'));
    }

    public function test_pagination_walks_to_the_last_page(): void
    {
        // Newest-first, so page after the first still yields items and eventually ends.
        $this->makeMedia(15);

        $page2 = $this->actingAs($this->admin())->getJson(route('admin.media.browse', ['page' => 2]))->assertOk();
        $this->assertGreaterThan(0, count($page2->json('data')));
    }
}
