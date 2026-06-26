<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuotationRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:quotations.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'price_tier' => in_array($this->input('price_tier'), ['retail', 'wholesale'], true) ? $this->input('price_tier') : 'retail',
            'discount_total' => $this->input('discount_total') ?: 0,
            'tax_total' => $this->input('tax_total') ?: 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', Rule::exists('customers', 'id')],
            'valid_until' => ['nullable', 'date'],
            'price_tier' => ['required', Rule::in(['retail', 'wholesale'])],
            'discount_total' => ['numeric', 'min:0'],
            'tax_total' => ['numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', Rule::exists('product_variants', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one line item.',
            'items.*.product_variant_id.required' => 'Choose a product for every line.',
        ];
    }
}
