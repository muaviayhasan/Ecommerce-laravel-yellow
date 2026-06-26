<?php

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:brands.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'logo_media_id' => $this->filled('logo_media_id') ? $this->input('logo_media_id') : null,
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
        $brandId = $this->route('brand')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($brandId)],
            'logo_media_id' => ['nullable', Rule::exists('media', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Append -2, -3, … until the slug is free (ignoring the row being edited). */
    private function uniqueSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'brand';
        $ignoreId = $this->route('brand')?->id;
        $slug = $base;
        $i = 2;

        while (
            Brand::query()
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
