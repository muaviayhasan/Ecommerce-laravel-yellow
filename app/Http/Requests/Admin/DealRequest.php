<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DealRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:deals.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: (string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
            'discount_type' => in_array($this->input('discount_type'), ['fixed', 'percent'], true) ? $this->input('discount_type') : 'fixed',
            'discount_value' => $this->filled('discount_value') ? $this->input('discount_value') : 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $dealId = $this->route('deal')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('deals', 'slug')->ignore($dealId)->withoutTrashed()],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'discount_type' => ['required', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'distinct', Rule::exists('product_variants', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
        ];
    }

    /** Percentage discount can't exceed 100% (a fixed amount is capped at the subtotal). */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('discount_type') === 'percent' && (float) $this->input('discount_value') > 100) {
                $validator->errors()->add('discount_value', 'Discount percentage cannot be more than 100%.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one product to the deal.',
            'items.min' => 'Add at least one product to the deal.',
            'items.*.variant_id.distinct' => 'The same product variant is in the deal twice.',
        ];
    }
}
