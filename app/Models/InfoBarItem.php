<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A single item in the storefront home "info bar" (the icon + title + subtitle
 * strip: Free Delivery, Secure Payment, …). Managed from Admin → Ecommerce → Info Bar.
 */
class InfoBarItem extends Model
{
    protected $fillable = ['icon', 'title', 'subtitle', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
