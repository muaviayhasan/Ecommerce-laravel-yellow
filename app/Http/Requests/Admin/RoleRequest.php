<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:roles.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'permissions' => array_values((array) $this->input('permissions', [])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => [
                'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9 _-]+$/',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($roleId),
            ],
            'permissions' => ['array'],
            'permissions.*' => [Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Use only letters, numbers, spaces, dashes or underscores.',
        ];
    }
}
