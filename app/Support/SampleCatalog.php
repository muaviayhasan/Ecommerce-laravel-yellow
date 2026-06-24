<?php

namespace App\Support;

use App\Http\Controllers\Storefront\Concerns\ProvidesSampleProducts;
use Illuminate\Support\Collection;

/**
 * Injectable wrapper around the PLACEHOLDER catalog data so views rendered
 * outside the controller flow (e.g. the errors/404 page) can pull the same
 * sample products without depending on a view composer firing. Replace with a
 * real catalog/repository service when the Products module lands.
 */
class SampleCatalog
{
    use ProvidesSampleProducts;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function products(): Collection
    {
        return $this->sampleProducts();
    }

    /**
     * Placeholder category list with counts, for sidebar widgets.
     *
     * @return array<string, int>
     */
    public function categories(): array
    {
        return [
            'Accessories' => 10,
            'Bluetooth Speakers' => 7,
            'Cameras & Photography' => 5,
            'Computer Components' => 1,
            'Gadgets' => 3,
            'Headphones' => 7,
            'Home Entertainment' => 1,
            'Laptops & Computers' => 12,
            'Smart Phones & Tablets' => 25,
            'Video Games & Consoles' => 3,
        ];
    }
}
