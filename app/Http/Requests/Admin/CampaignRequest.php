<?php

namespace App\Http\Requests\Admin;

use App\Models\EmailCampaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:campaigns.* middleware. */
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
            'subject' => ['required', 'string', 'max:255'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'audience' => ['required', Rule::in(EmailCampaign::AUDIENCES)],
            'coupon_id' => ['nullable', Rule::exists('coupons', 'id')],
            'body' => ['required', 'string', 'max:100000'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
