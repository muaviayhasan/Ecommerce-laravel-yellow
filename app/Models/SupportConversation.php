<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class SupportConversation extends Model
{
    protected $fillable = [
        'user_id', 'name', 'email', 'token', 'status', 'last_message_at', 'blocked_at', 'last_seen_at',
    ];

    /** How recently the widget must have checked in to count as "online". */
    private const ONLINE_WINDOW_SECONDS = 45;

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'blocked_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }

    /** A logged-in customer whose email is verified gets the blue tick. */
    public function isVerified(): bool
    {
        return $this->user_id !== null && $this->user?->email_verified_at !== null;
    }

    /** A blocked customer can still read the thread but can no longer send messages. */
    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    /** True when the customer's widget has checked in within the online window. */
    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->greaterThan(now()->subSeconds(self::ONLINE_WINDOW_SECONDS));
    }

    /** The (unguessable) token used as the public broadcast channel name; created on demand. */
    public function channelToken(): string
    {
        if (blank($this->token)) {
            $this->forceFill(['token' => Str::random(48)])->save();
        }

        return $this->token;
    }
}
