<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    use HasFactory;

    public const AUDIENCES = ['all_customers', 'retail', 'wholesale', 'subscribers'];

    protected $fillable = [
        'subject', 'preheader', 'body', 'audience', 'coupon_id',
        'status', 'scheduled_at', 'recipients_count', 'sent_count', 'created_by', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    /** Human label for the audience code. */
    public function audienceLabel(): string
    {
        return match ($this->audience) {
            'all_customers' => 'All customers',
            'retail' => 'Retail customers',
            'wholesale' => 'Wholesale customers',
            'subscribers' => 'Newsletter subscribers',
            default => ucfirst((string) $this->audience),
        };
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled'], true);
    }
}
