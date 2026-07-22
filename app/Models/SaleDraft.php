<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDraft extends Model
{
    protected $fillable = ['channel', 'label', 'customer_id', 'payload', 'created_by'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
