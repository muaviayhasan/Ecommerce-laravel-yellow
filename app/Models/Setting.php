<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['group', 'key', 'value', 'type'];

    /**
     * Decode the stored value according to its declared type.
     */
    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'bool', 'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'int', 'integer' => (int) $this->value,
            'float', 'decimal' => (float) $this->value,
            'json', 'array' => json_decode((string) $this->value, true),
            // blank secrets decode to null; tolerate malformed ciphertext rather than 500.
            'encrypted' => blank($this->value) ? null : rescue(fn () => decrypt($this->value), null, report: false),
            default => $this->value,
        };
    }

    /**
     * A group's stored (decoded) values merged over the given defaults
     * (CONVENTIONS §6.2). Stored rows win; missing keys fall back to $defaults.
     *
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public static function groupWithDefaults(string $group, array $defaults = []): array
    {
        $stored = static::query()
            ->where('group', $group)
            ->get()
            ->mapWithKeys(fn (self $s) => [$s->key => $s->typed_value])
            ->all();

        return array_replace($defaults, $stored);
    }

    /**
     * Upsert a group's values, encoding each by its declared type (§6.2).
     * `encrypted` values arrive already-plaintext and are encrypted here; pass
     * only the keys you intend to write (callers drop blank secrets to keep them).
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, string>  $types  key => type (defaults to 'string')
     */
    public static function putGroup(string $group, array $values, array $types = []): void
    {
        foreach ($values as $key => $value) {
            $type = $types[$key] ?? 'string';

            $encoded = match ($type) {
                'bool', 'boolean' => $value ? '1' : '0',
                'int', 'integer' => (string) (int) $value,
                'float', 'decimal' => (string) (float) $value,
                'json', 'array' => json_encode($value),
                'encrypted' => $value === null ? null : encrypt($value),
                default => (string) $value,
            };

            static::updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => $encoded, 'type' => $type],
            );
        }
    }
}
