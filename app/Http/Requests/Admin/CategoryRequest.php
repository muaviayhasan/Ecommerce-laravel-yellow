<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:categories.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise inputs before validation: resolve toggle, default sort, and
     * auto-generate a unique slug from the name when one wasn't supplied.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->filled('sort_order') ? (int) $this->input('sort_order') : 0,
            // Empty <select>/number fields → real null so `nullable` short-circuits `exists`.
            'parent_id' => $this->filled('parent_id') ? $this->input('parent_id') : null,
            'image_media_id' => $this->filled('image_media_id') ? $this->input('image_media_id') : null,
            'meta_image_media_id' => $this->filled('meta_image_media_id') ? $this->input('meta_image_media_id') : null,
            'markup_percent' => $this->filled('markup_percent') ? $this->input('markup_percent') : null,
        ]);

        if (blank($this->input('slug')) && filled($this->input('name'))) {
            $this->merge(['slug' => $this->uniqueSlug(Str::slug((string) $this->input('name')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id'),
                // A category can't be its own parent.
                Rule::notIn(array_filter([$categoryId])),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'markup_percent' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'is_active' => ['boolean'],
            'image_media_id' => ['nullable', Rule::exists('media', 'id')],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'meta_image_media_id' => ['nullable', Rule::exists('media', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.not_in' => 'A category cannot be its own parent.',
        ];
    }

    /** Append -2, -3, … until the slug is free (ignoring the row being edited). */
    private function uniqueSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'category';
        $ignoreId = $this->route('category')?->id;
        $slug = $base;
        $i = 2;

        while (
            Category::query()
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
