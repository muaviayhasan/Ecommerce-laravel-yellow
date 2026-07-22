<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use SoftDeletes;

    // Deal type: the whole set at one price vs individually priced items.
    public const TYPE_BUNDLE = 'bundle';
    public const TYPE_SALE = 'sale';

    protected $fillable = [
        'name', 'slug', 'description', 'image_media_id', 'type', 'bundle_price',
        'discount_type', 'discount_value',
        'starts_at', 'ends_at', 'is_active', 'show_on_home', 'is_spotlight', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'bundle_price' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'show_on_home' => 'boolean',
            'is_spotlight' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(DealItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Active and inside its date window (open-ended when dates are blank). */
    public function scopeLive(Builder $query): Builder
    {
        return $query->active()
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /** Live deals flagged to appear in the home-page deal areas, in display order. */
    public function scopeForHome(Builder $query): Builder
    {
        return $query->live()->where('show_on_home', true)
            ->orderBy('sort_order')->orderByDesc('id');
    }

    /** Live now / scheduled / expired / inactive — for badges and stats. */
    public function status(): string
    {
        return match (true) {
            ! $this->is_active => 'inactive',
            $this->starts_at !== null && $this->starts_at->isFuture() => 'scheduled',
            $this->ends_at !== null && $this->ends_at->isPast() => 'expired',
            default => 'live',
        };
    }

    /** Sum of the items' regular retail (× quantity) — the “was” price. */
    public function retailTotal(): float
    {
        return (float) $this->items->sum(
            fn (DealItem $item) => (float) ($item->variant?->retail_price ?? 0) * (float) $item->quantity
        );
    }

    /** The deal discount in money, capped so the total never goes negative. */
    public function discountAmount(): float
    {
        $subtotal = $this->retailTotal();
        $value = (float) $this->discount_value;

        return round($this->discount_type === 'percent'
            ? $subtotal * min($value, 100) / 100
            : min($value, $subtotal), 2);
    }

    /** What the whole deal sells for: items total minus the deal discount. */
    public function dealTotal(): float
    {
        return round($this->retailTotal() - $this->discountAmount(), 2);
    }
}
