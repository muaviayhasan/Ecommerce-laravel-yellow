<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HeroSlideRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:hero-slides.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    /** Normalise toggle, default sort, and empty media/URL fields to null. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->filled('sort_order') ? (int) $this->input('sort_order') : 0,
            'image_media_id' => $this->filled('image_media_id') ? $this->input('image_media_id') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kicker' => ['nullable', 'string', 'max:255'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'tail' => ['nullable', 'string', 'max:255'],
            'highlight' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:255'],
            'cta_url' => ['nullable', 'string', 'max:2048'],
            'image_media_id' => ['nullable', Rule::exists('media', 'id')],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
        ];
    }
}
