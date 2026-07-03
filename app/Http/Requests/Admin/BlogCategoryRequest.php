<?php

namespace App\Http\Requests\Admin;

use App\Models\BlogCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogCategoryRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:blog-categories.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $sort = $this->input('sort_order');
        $this->merge([
            // Blank → next available number; an explicit value (incl. 0) is kept.
            'sort_order' => is_numeric($sort) ? (int) $sort : (int) BlogCategory::max('sort_order') + 1,
            'parent_id' => $this->filled('parent_id') ? $this->input('parent_id') : null,
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
        $id = $this->route('category')?->id;

        // A new category can sit at positions 1..(current highest + 1); an edit can
        // reach the current highest. Keeps the Sort value meaningful, not arbitrary.
        $maxSort = (int) BlogCategory::max('sort_order');
        $sortCeiling = $id ? max(1, $maxSort) : $maxSort + 1;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('blog_categories', 'slug')->ignore($id)],
            'parent_id' => ['nullable', Rule::exists('blog_categories', 'id')->whereNot('id', $id)],
            'sort_order' => ['integer', 'min:1', 'max:' . $sortCeiling],
        ];
    }

    private function uniqueSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'category';
        $id = $this->route('category')?->id;
        $slug = $base;
        $i = 2;

        while (BlogCategory::where('slug', $slug)->when($id, fn ($q) => $q->whereKeyNot($id))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
