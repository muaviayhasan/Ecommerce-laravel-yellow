<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BomRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:boms.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'output_quantity' => $this->filled('output_quantity') ? $this->input('output_quantity') : 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', Rule::exists('products', 'id')],
            'name' => ['nullable', 'string', 'max:255'],
            'output_quantity' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'labor_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'overhead_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_active' => ['boolean'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.component_variant_id' => ['required', Rule::exists('product_variants', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'items.*.waste_percent' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'A BOM needs at least one component.',
            'items.*.component_variant_id.required' => 'Choose a component for each line.',
        ];
    }
}
