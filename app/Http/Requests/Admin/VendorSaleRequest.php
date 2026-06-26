<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorSaleRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:orders.create middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'paid' => $this->filled('paid') ? $this->input('paid') : 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', Rule::exists('customers', 'id')],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bank', 'credit'])],
            'paid' => ['numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', Rule::exists('product_variants', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Choose the vendor / customer this sale is for.',
            'items.required' => 'Add at least one item to the sale.',
        ];
    }
}
