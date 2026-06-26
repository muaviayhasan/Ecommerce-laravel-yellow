<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:production.* middleware. */
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
            'bom_id' => ['required', Rule::exists('boms', 'id')],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
