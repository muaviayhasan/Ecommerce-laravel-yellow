<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    protected $fillable = [
        'support_conversation_id', 'from_admin', 'user_id', 'body', 'read_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'from_admin' => 'boolean',
            'read_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'support_conversation_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
