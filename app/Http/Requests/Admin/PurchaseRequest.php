<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:purchases.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')],
            'purchase_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'discount_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            // Delivery (optional)
            'delivery_method' => ['nullable', Rule::in(['pickup', 'own_rider', 'courier', 'other'])],
            'delivery_agent' => ['nullable', 'string', 'max:255'],
            'delivery_contact' => ['nullable', 'string', 'max:255'],
            'delivery_charge' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'tax_total' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'paid_total' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', Rule::exists('product_variants', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
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
                    ->sum(fn ($i) => (float) ($i['quantity'] ?? 0) * (float) ($i['unit_cost'] ?? 0));

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
            'items.*.product_variant_id.required' => 'Choose a product for each line.',
            'items.*.quantity.gt' => 'Quantity must be greater than zero.',
        ];
    }
}
