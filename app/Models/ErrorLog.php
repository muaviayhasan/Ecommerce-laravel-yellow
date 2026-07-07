<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A captured application exception. One row per unique fingerprint; `occurrences`
 * counts how often it has fired and `resolved_at` marks it handled (a resolved
 * error re-opens automatically if it happens again).
 */
class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'fingerprint', 'level', 'type', 'message', 'code', 'file', 'line',
        'url', 'method', 'user_id', 'ip_address', 'context', 'trace',
        'occurrences', 'last_seen_at', 'resolved_at', 'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'line' => 'integer',
            'occurrences' => 'integer',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /** The exception class without its namespace, e.g. "QueryException". */
    public function shortType(): string
    {
        return class_basename((string) $this->type);
    }
}
