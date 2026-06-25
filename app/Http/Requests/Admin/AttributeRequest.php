<?php

namespace App\Http\Requests\Admin;

use App\Models\Attribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AttributeRequest extends FormRequest
{
    /** Authorization is enforced by the controller's can:attributes.* middleware. */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_variation' => $this->boolean('is_variation'),
            'sort_order' => $this->filled('sort_order') ? (int) $this->input('sort_order') : 0,
        ]);

        if (blank($this->input('code')) && filled($this->input('name'))) {
            $this->merge(['code' => $this->uniqueCode(Str::slug((string) $this->input('name')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $attributeId = $this->route('attribute')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('attributes', 'code')->ignore($attributeId)],
            'type' => ['required', 'in:select,swatch,radio'],
            'is_variation' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],

            // Repeatable values (blank-label rows are dropped by the controller).
            'values' => ['array'],
            'values.*.id' => ['nullable', 'integer'],
            'values.*.label' => ['nullable', 'string', 'max:255'],
            'values.*.value' => ['nullable', 'string', 'max:255'],
            'values.*.color_hex' => ['nullable', 'string', 'max:9'],
            'values.*.sort_order' => ['nullable', 'integer'],
        ];
    }

    /** Append -2, -3, … until the code is free (ignoring the row being edited). */
    private function uniqueCode(string $base): string
    {
        $base = $base !== '' ? $base : 'attribute';
        $ignoreId = $this->route('attribute')?->id;
        $code = $base;
        $i = 2;

        while (
            Attribute::query()
                ->where('code', $code)
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $code = "{$base}-{$i}";
            $i++;
        }

        return $code;
    }
}
