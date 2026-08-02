<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Finds the slug problems an SEO audit picks up on, and fixes the one class of
 * them that can be fixed mechanically.
 *
 *   php artisan catalog:slugs          report only, changes nothing
 *   php artisan catalog:slugs --fix    additionally lowercases mixed-case slugs
 *
 * What it will NOT do, deliberately: delete or merge anything. A "-copy" product
 * might be a mistake from the duplicate button or it might be a real variant
 * somebody built on purpose, and a "-2" slug might be a genuine second model.
 * Only a person looking at the catalogue can tell, so those are reported with
 * their URLs and left alone.
 *
 * Lowercasing is safe because ProductController 301s any request whose slug
 * doesn't exactly match the stored one — so an old mixed-case URL keeps working
 * and redirects to the canonical form rather than 404ing.
 */
class AuditProductSlugs extends Command
{
    protected $signature = 'catalog:slugs {--fix : Lowercase mixed-case slugs (still reports everything else)}';

    protected $description = 'Report duplicate, mixed-case and "-copy" product slugs; optionally lowercase them';

    public function handle(): int
    {
        $products = Product::withTrashed()->get(['id', 'name', 'slug', 'sku', 'deleted_at', 'is_web_listed', 'published_at']);
        $fix = (bool) $this->option('fix');
        $problems = 0;

        // 1. Mixed case ------------------------------------------------------------
        // A case-insensitive database resolves /product/PEL-x and /product/pel-x to
        // the same row, so both are addressable and compete as duplicate content.
        $mixed = $products->filter(fn ($p) => $p->slug !== Str::lower($p->slug));

        $this->newLine();
        $this->components->info("Mixed-case slugs: {$mixed->count()}");

        foreach ($mixed as $p) {
            $target = Str::lower($p->slug);
            $taken = $products->first(fn ($o) => $o->id !== $p->id && Str::lower($o->slug) === $target);

            if ($taken) {
                $this->components->twoColumnDetail(
                    "  <fg=red>{$p->slug}</>",
                    "collides with #{$taken->id} — skipped"
                );
                $problems++;

                continue;
            }

            if ($fix) {
                $p->timestamps = false;
                $p->forceFill(['slug' => $target])->saveQuietly();
                $this->components->twoColumnDetail("  {$p->slug}", "<fg=green>→ {$target}</>");
            } else {
                $this->components->twoColumnDetail("  {$p->slug}", "<fg=yellow>would become {$target}</>");
                $problems++;
            }
        }

        // 2. "-copy" leakage from the admin duplicate action ------------------------
        $copies = $products->filter(fn ($p) => (bool) preg_match('/-copy(-\d+)?$/', $p->slug));

        $this->newLine();
        $this->components->info("Slugs ending in \"-copy\": {$copies->count()}  (review by hand — never auto-deleted)");

        foreach ($copies as $p) {
            $this->components->twoColumnDetail("  /product/{$p->slug}", $this->state($p));
            $problems++;
        }

        // 3. Numeric suffixes whose base slug also exists ---------------------------
        $bySlug = $products->keyBy('slug');
        $numbered = $products->filter(function ($p) use ($bySlug) {
            return preg_match('/^(.*)-(\d+)$/', $p->slug, $m) && $bySlug->has($m[1]);
        });

        $this->newLine();
        $this->components->info("Numbered slugs whose base also exists: {$numbered->count()}  (likely duplicates)");

        foreach ($numbered as $p) {
            preg_match('/^(.*)-(\d+)$/', $p->slug, $m);
            $this->components->twoColumnDetail("  /product/{$p->slug}", "base: /product/{$m[1]}  " . $this->state($p));
            $problems++;
        }

        $this->newLine();

        if ($problems === 0) {
            $this->components->info('No slug problems found.');
        } elseif (! $fix && $mixed->isNotEmpty()) {
            $this->components->warn('Re-run with --fix to lowercase the mixed-case slugs. Duplicates are never touched automatically.');
        }

        return self::SUCCESS;
    }

    /** Whether a row is actually reachable, so you know what you're looking at. */
    private function state(Product $p): string
    {
        if ($p->deleted_at) {
            return '<fg=gray>deleted</>';
        }

        return ($p->is_web_listed && $p->published_at)
            ? '<fg=red>LIVE on the storefront</>'
            : '<fg=gray>draft/hidden</>';
    }
}
