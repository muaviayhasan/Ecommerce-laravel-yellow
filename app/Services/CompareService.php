<?php

namespace App\Services;

class CompareService extends ProductShortlist
{
    protected function key(): string
    {
        return 'compare';
    }

    /** Compare at most four products side by side. */
    protected function limit(): ?int
    {
        return 4;
    }
}
