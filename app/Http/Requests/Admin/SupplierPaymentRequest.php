<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierPaymentRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:purchases.pay middleware. */
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
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'paid_on' => ['required', 'date'],
            'method' => ['required', Rule::in(['cash', 'bank'])],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** Cap the payment at the purchase's outstanding balance. */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $purchase = $this->route('purchase');
            if (! $purchase) {
                return;
            }

            $outstanding = $purchase->outstanding();
            if ($outstanding <= 0) {
                $validator->errors()->add('amount', 'This purchase is already fully paid.');

                return;
            }

            if ((float) $this->input('amount') > $outstanding) {
                $validator->errors()->add('amount', "Payment cannot exceed the outstanding balance of {$outstanding}.");
            }
        });
    }
}
