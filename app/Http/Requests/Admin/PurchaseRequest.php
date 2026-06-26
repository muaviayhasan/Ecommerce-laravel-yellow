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
            'tax_total' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'paid_total' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', Rule::exists('product_variants', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
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
