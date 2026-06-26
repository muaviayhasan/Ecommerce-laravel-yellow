<?php

namespace Tests\Feature\Admin;

use App\Services\PricingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function pricing(): PricingService
    {
        return app(PricingService::class);
    }

    public function test_retail_applies_the_configured_markup(): void
    {
        $service = $this->pricing();
        $expected = round(200 * (1 + $service->markupPercent() / 100), 2);

        $this->assertSame($expected, $service->suggestRetail(200));
    }

    public function test_wholesale_applies_the_configured_discount(): void
    {
        $service = $this->pricing();
        $expected = round(500 * (1 - $service->wholesaleDiscountPercent() / 100), 2);

        $this->assertSame($expected, $service->suggestWholesale(500));
    }

    public function test_suggest_chains_retail_then_wholesale(): void
    {
        $service = $this->pricing();
        $suggestion = $service->suggest(100);

        $this->assertSame($service->suggestRetail(100), $suggestion['retail']);
        $this->assertSame($service->suggestWholesale($suggestion['retail']), $suggestion['wholesale']);
    }

    public function test_defaults_are_sane_when_unconfigured(): void
    {
        $service = $this->pricing();

        // With no pricing settings saved, the service falls back to 30% / 10%.
        $this->assertGreaterThanOrEqual(0, $service->markupPercent());
        $this->assertGreaterThanOrEqual(0, $service->wholesaleDiscountPercent());
        $this->assertLessThanOrEqual(100, $service->wholesaleDiscountPercent());
    }

    public function test_zero_and_negative_cost_floor_at_zero(): void
    {
        $service = $this->pricing();

        $this->assertSame(0.0, $service->suggestRetail(0));
        $this->assertSame(0.0, $service->suggestRetail(-50));
    }
}
