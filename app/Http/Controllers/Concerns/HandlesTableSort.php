<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Http\Request;

/**
 * Shared, allow-listed sorting + per-page for admin index tables. Pair with the
 * <x-admin.sort-header> (clickable column headers) and <x-admin.per-page> (rows
 * selector) view components. Filters/search/page are preserved by those components.
 */
trait HandlesTableSort
{
    /**
     * Apply ?sort=&dir= to a query, restricted to an allow-list.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array<string, string|Closure>  $columns  sort key => column name, or closure(query, string $dir)
     * @param  Closure  $default  applies the default order when no valid ?sort is given
     */
    protected function applyTableSort($query, Request $request, array $columns, Closure $default): void
    {
        $sort = (string) $request->string('sort');
        $dir = strtolower((string) $request->string('dir')) === 'asc' ? 'asc' : 'desc';

        if (isset($columns[$sort])) {
            $target = $columns[$sort];
            $target instanceof Closure ? $target($query, $dir) : $query->orderBy($target, $dir);
        } else {
            $default($query);
        }
    }

    /** Page size: an allow-listed ?per_page override, else the store default. */
    protected function perPageFor(Request $request): int
    {
        $pp = $request->integer('per_page');

        return in_array($pp, [15, 25, 50, 100], true) ? $pp : per_page();
    }
}
