<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'label', 'name', 'company', 'phone',
        'line1', 'line2', 'city', 'state', 'zip', 'country',
        'latitude', 'longitude',
        'is_default_billing', 'is_default_shipping',
    ];

    protected function casts(): array
    {
        return [
            'is_default_billing' => 'boolean',
            'is_default_shipping' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
