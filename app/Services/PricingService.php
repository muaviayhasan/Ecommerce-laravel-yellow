<?php

namespace App\Services;

/**
 * Pricing (§8). Suggests selling prices from cost using the markup + wholesale
 * discount configured on the Pricing settings tab. Purely advisory — the values
 * pre-fill the product form and stay fully editable.
 */
class PricingService
{
    public function markupPercent(): float
    {
        return max(0, (float) setting('pricing', 'default_markup_percent', 30));
    }

    public function wholesaleDiscountPercent(): float
    {
        return min(100, max(0, (float) setting('pricing', 'wholesale_discount_percent', 10)));
    }

    /** Retail = cost × (1 + markup%). */
    public function suggestRetail(float $cost): float
    {
        return round(max(0, $cost) * (1 + $this->markupPercent() / 100), 2);
    }

    /** Wholesale = retail − wholesale discount%. */
    public function suggestWholesale(float $retail): float
    {
        return round(max(0, $retail) * (1 - $this->wholesaleDiscountPercent() / 100), 2);
    }

    /**
     * Suggested retail + wholesale for a given cost.
     *
     * @return array{retail: float, wholesale: float}
     */
    public function suggest(float $cost): array
    {
        $retail = $this->suggestRetail($cost);

        return ['retail' => $retail, 'wholesale' => $this->suggestWholesale($retail)];
    }
}
