<?php

namespace Tests\Unit;

use App\Models\Media;
use Tests\TestCase;

class MediaTest extends TestCase
{
    public function test_public_media_url_is_root_relative(): void
    {
        // Root-relative so images resolve against the serving host:port, not APP_URL.
        $media = new Media(['disk' => 'public', 'path' => 'gallery/photo.jpg']);

        $this->assertSame('/storage/gallery/photo.jpg', $media->url);
    }
}
