<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use HasFactory;

    /**
     * Allowed status transitions (the "Mark …" actions offered per status). A draft
     * is sent; a sent quote is accepted / rejected / expired; an accepted quote can
     * only be converted (no reject); rejected / expired quotes can be re-sent;
     * converted quotes are locked.
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        'draft' => ['sent'],
        'sent' => ['accepted', 'rejected', 'expired'],
        'accepted' => [],
        'rejected' => ['sent'],
        'expired' => ['sent'],
        'converted' => [],
    ];

    protected $fillable = [
        'quotation_number', 'customer_id', 'status', 'valid_until', 'price_tier',
        'subtotal', 'discount_type', 'discount_value', 'discount_total', 'tax_total', 'grand_total',
        'notes', 'converted_order_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Statuses this quotation may move to next (the allowed "Mark …" actions).
     *
     * @return list<string>
     */
    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    /** Whether the quote may transition to the given status from where it is now. */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
