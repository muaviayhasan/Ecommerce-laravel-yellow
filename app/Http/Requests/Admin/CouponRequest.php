<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:coupons.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'type' => in_array($this->input('type'), ['percent', 'fixed'], true) ? $this->input('type') : 'percent',
            'is_active' => $this->boolean('is_active'),
            'first_order_only' => $this->boolean('first_order_only'),
            'min_subtotal' => $this->filled('min_subtotal') ? $this->input('min_subtotal') : null,
            'max_uses' => $this->filled('max_uses') ? $this->input('max_uses') : null,
            'usage_limit_per_customer' => $this->filled('usage_limit_per_customer') ? $this->input('usage_limit_per_customer') : null,
            'customer_ids' => array_values(array_filter((array) $this->input('customer_ids', []))),
            'starts_at' => $this->filled('starts_at') ? $this->input('starts_at') : null,
            'expires_at' => $this->filled('expires_at') ? $this->input('expires_at') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        $expires = ['nullable', 'date'];
        if ($this->filled('starts_at')) {
            $expires[] = 'after_or_equal:starts_at';
        }

        return [
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9._-]+$/', Rule::unique('coupons', 'code')->ignore($couponId)],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => array_merge(['required', 'numeric', 'min:0'], $this->input('type') === 'percent' ? ['max:100'] : ['max:99999999.99']),
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'first_order_only' => ['boolean'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer', Rule::exists('customers', 'id')],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => $expires,
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Use only letters, numbers, dots, dashes or underscores.',
            'value.max' => 'A percentage coupon cannot exceed 100%.',
        ];
    }
}
