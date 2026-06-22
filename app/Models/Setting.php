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
            'encrypted' => $this->value === null ? null : decrypt($this->value),
            default => $this->value,
        };
    }
}
