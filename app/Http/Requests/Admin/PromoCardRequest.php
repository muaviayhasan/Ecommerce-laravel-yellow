<?php

namespace App\Http\Requests\Admin;

use App\Models\PromoCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromoCardRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:promo-cards.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->filled('sort_order') ? (int) $this->input('sort_order') : 0,
            'image_media_id' => $this->filled('image_media_id') ? $this->input('image_media_id') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kicker' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'display_type' => ['required', Rule::in([PromoCard::TYPE_SHOP, PromoCard::TYPE_PRICE, PromoCard::TYPE_PERCENT])],
            'prefix' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:8'],
            'amount' => ['nullable', 'string', 'max:32'],
            'cents' => ['nullable', 'string', 'max:8'],
            'url' => ['nullable', 'string', 'max:2048'],
            'image_media_id' => ['nullable', Rule::exists('media', 'id')],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
        ];
    }
}
