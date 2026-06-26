<?php

namespace App\Http\Requests\Admin;

use App\Models\BlogTag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogTagRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:blog-tags.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && filled($this->input('name'))) {
            $this->merge(['slug' => $this->uniqueSlug(Str::slug((string) $this->input('name')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('tag')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('blog_tags', 'slug')->ignore($id)],
        ];
    }

    private function uniqueSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'tag';
        $id = $this->route('tag')?->id;
        $slug = $base;
        $i = 2;

        while (BlogTag::where('slug', $slug)->when($id, fn ($q) => $q->whereKeyNot($id))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
