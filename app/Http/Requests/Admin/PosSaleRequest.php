<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PosSaleRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:pos.sell middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'discount_type' => in_array($this->input('discount_type'), ['fixed', 'percent'], true) ? $this->input('discount_type') : 'fixed',
            'discount_value' => $this->filled('discount_value') ? $this->input('discount_value') : 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', Rule::exists('customers', 'id')],
            // Optional walk-in contact details, printed on the bill.
            'walk_in_name' => ['nullable', 'string', 'max:100'],
            'walk_in_phone' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'qr'])],
            'discount_type' => ['required', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            // Delivery (optional)
            'shipping_method' => ['nullable', Rule::in(['pickup', 'own_rider', 'courier', 'other'])],
            'courier' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'shipping_total' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', Rule::exists('product_variants', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
        ];
    }

    /** Percentage discount can't exceed 100% (a fixed amount is capped at the subtotal in the service). */
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
            'items.required' => 'Add at least one item to the sale.',
        ];
    }
}
