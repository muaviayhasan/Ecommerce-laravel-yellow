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
            'discount_type' => in_array($this->input('discount_type'), ['fixed', 'percent'], true) ? $this->input('discount_type') : 'fixed',
            'discount_value' => $this->filled('discount_value') ? $this->input('discount_value') : 0,
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
            'discount_type' => ['required', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'tax_total' => ['numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', Rule::exists('product_variants', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** Cap the discount: percent ≤ 100%, fixed ≤ the items subtotal. */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('discount_type', 'fixed');
            $value = (float) $this->input('discount_value', 0);

            if ($type === 'percent' && $value > 100) {
                $validator->errors()->add('discount_value', 'Discount percentage cannot be more than 100%.');
            }

            if ($type === 'fixed') {
                $subtotal = collect($this->input('items', []))
                    ->sum(fn ($i) => (float) ($i['quantity'] ?? 0) * (float) ($i['unit_price'] ?? 0));

                if ($value > round($subtotal, 2)) {
                    $validator->errors()->add('discount_value', 'Discount cannot be more than the subtotal.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one line item.',
            'items.*.product_variant_id.required' => 'Choose a product for every line.',
        ];
    }
}
