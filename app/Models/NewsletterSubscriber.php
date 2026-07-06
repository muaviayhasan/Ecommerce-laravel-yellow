<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email', 'name', 'token', 'source', 'subscribed_at', 'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Guarantee an unsubscribe token on every row.
        static::creating(function (self $subscriber) {
            $subscriber->token ??= Str::random(48);
            $subscriber->subscribed_at ??= now();
        });
    }

    public function isSubscribed(): bool
    {
        return $this->unsubscribed_at === null;
    }

    /** Active (still-subscribed) rows. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at');
    }
}
