<?php

namespace App\Http\Controllers\Storefront\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * PLACEHOLDER blog content shared by the storefront blog controller so the theme
 * renders before the real Blog module (blog_posts/categories/tags) is wired up.
 * Replace the callers with real queries (BlogPost::published()->latest()…) later.
 */
trait ProvidesSampleBlog
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function samplePosts(): Collection
    {
        $body = [
            'Lorem Ipsum is simply dummy text of the printing and typesetting industry. It has survived not only '
                . 'five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.',
            'The exploration of space continues to yield surprising results, as we witness the delicate blooming of '
                . 'terrestrial life in zero-gravity environments — a milestone for long-duration missions.',
            'Scientific advancement in the hardware sector is rapidly evolving. We are looking at a new era where '
                . 'portable electronics aren\'t just tools, but extensions of our environmental awareness.',
        ];

        $posts = [
            [
                'title' => 'Robot Wars – Post with Gallery',
                'format' => 'gallery',
                'category' => 'Design, Technology',
                'date' => 'March 4, 2024',
                'comments' => 3,
                'excerpt' => 'Exploring the latest advancements in competitive robotics and the design philosophies behind '
                    . 'the world\'s most agile automated units — intricate internal mechanics and sleek outer shells.',
            ],
            [
                'title' => 'Robot Wars – Now Closed – Post with Audio',
                'format' => 'audio',
                'category' => 'News, Uncategorized',
                'date' => 'March 3, 2024',
                'comments' => 1,
                'excerpt' => 'Listen to our exclusive wrap-up podcast discussing the final moments of the seasonal '
                    . 'championship. We interview the lead designers and reflect on the unexpected final rounds.',
            ],
            [
                'title' => 'Robot Wars – Now Closed – Post with Video',
                'format' => 'video',
                'category' => 'Uncategorized, Videos',
                'date' => 'March 3, 2024',
                'comments' => 0,
                'excerpt' => 'Watch the high-definition highlights from the final match. Experience every mechanical '
                    . 'crunch and tactical maneuver captured with high-speed cameras and expert commentary.',
            ],
            [
                'title' => 'Announcement – Post without Image',
                'format' => 'text',
                'category' => 'Events, News',
                'date' => 'March 2, 2024',
                'comments' => 2,
                'excerpt' => 'A text-focused announcement regarding upcoming updates to our membership program. New '
                    . 'reward tiers and early-access privileges are arriving for our loyal customers next month.',
            ],
            [
                'title' => 'SpaceX Falcon explodes after Landing',
                'format' => 'standard',
                'category' => 'Design, Technology',
                'date' => 'March 1, 2024',
                'comments' => 5,
                'excerpt' => 'Analyzing the complex telemetry data from the recent orbital mission. The structural '
                    . 'failure in the final moments provides critical insights for future launches.',
            ],
            [
                'title' => 'The first flowers in space',
                'format' => 'standard',
                'category' => 'Design, Technology',
                'date' => 'March 1, 2024',
                'comments' => 4,
                'excerpt' => 'The exploration of space continues to yield surprising biological results, as we witness '
                    . 'the delicate blooming of terrestrial life in zero-gravity environments.',
            ],
        ];

        return collect($posts)->map(function (array $post, int $i) use ($body): array {
            $slug = Str::slug($post['title']);

            return [
                ...$post,
                'slug' => $slug,
                'url' => route('blog.show', $slug),
                'author' => 'admin',
                'image' => "https://picsum.photos/seed/usman-blog-{$i}/800/500",
                'body' => $body,
            ];
        })->values();
    }

    /**
     * @return array<string, int>
     */
    protected function blogCategories(): array
    {
        return [
            'Design' => 12,
            'Events' => 8,
            'Inspirations' => 15,
            'News' => 24,
            'Technology' => 31,
        ];
    }

    /**
     * @return list<string>
     */
    protected function blogTags(): array
    {
        return ['Amazon like', 'Awesome', 'bootstrap', 'buy it', 'clean design', 'electronics', 'theme', 'video post', 'wordpress'];
    }
}
