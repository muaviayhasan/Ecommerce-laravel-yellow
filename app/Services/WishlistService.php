<?php

namespace App\Services;

class WishlistService extends ProductShortlist
{
    protected function key(): string
    {
        return 'wishlist';
    }
}
