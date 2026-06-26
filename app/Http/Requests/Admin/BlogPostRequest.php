<?php

namespace App\Http\Requests\Admin;

use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogPostRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:blog-posts.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'no_index' => $this->boolean('no_index'),
            'status' => in_array($this->input('status'), ['draft', 'published'], true) ? $this->input('status') : 'draft',
            'cover_media_id' => $this->filled('cover_media_id') ? $this->input('cover_media_id') : null,
            'og_image_media_id' => $this->filled('og_image_media_id') ? $this->input('og_image_media_id') : null,
        ]);

        if (blank($this->input('slug')) && filled($this->input('title'))) {
            $this->merge(['slug' => $this->uniqueSlug(Str::slug((string) $this->input('title')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $postId = $this->route('post')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('blog_posts', 'slug')->ignore($postId)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'cover_media_id' => ['nullable', Rule::exists('media', 'id')],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'og_image_media_id' => ['nullable', Rule::exists('media', 'id')],
            'no_index' => ['boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => [Rule::exists('blog_categories', 'id')],
            'tags' => ['nullable', 'array'],
            'tags.*' => [Rule::exists('blog_tags', 'id')],
        ];
    }

    private function uniqueSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'post';
        $ignoreId = $this->route('post')?->id;
        $slug = $base;
        $i = 2;

        while (
            BlogPost::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
