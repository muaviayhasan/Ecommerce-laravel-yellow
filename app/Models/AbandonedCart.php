<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A stored cart that reached checkout but wasn't purchased. Recovery reminders
 * are scheduled off this row (see SendAbandonedCartRemindersJob); placing an
 * order sets recovered_at so the reminders stop.
 */
class AbandonedCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'email', 'name', 'items', 'subtotal', 'item_count',
        'token', 'reminders_sent', 'last_reminded_at', 'recovered_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'subtotal' => 'decimal:2',
            'item_count' => 'integer',
            'reminders_sent' => 'integer',
            'last_reminded_at' => 'datetime',
            'recovered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Guarantee an unguessable token for the recover link on every row.
        static::creating(function (self $cart) {
            $cart->token ??= Str::random(48);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Still open — not yet turned into an order. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('recovered_at');
    }
}
