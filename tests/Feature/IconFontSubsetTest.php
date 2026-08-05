<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The Material Symbols font is subsetted to the icons this project uses (313 KB
 * of the full font would otherwise sit on the mobile critical path, ahead of the
 * LCP image). The trade-off is that an icon nobody subsetted for does not render
 * as a missing glyph — it renders as its own name, the literal word
 * "shopping_cart", in the middle of the page.
 *
 * That failure is invisible to every other test and easy to miss in review, so it
 * is caught here instead: add an icon to a Blade view, forget to re-run
 * tools/subset-icon-font.py, and this fails.
 */
class IconFontSubsetTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Icons that are not in the upstream font at all, so subsetting cannot
     * help — swap the view to an existing glyph instead of listing here
     * (location_on → pin_drop, auto_fix_high → auto_awesome, …).
     */
    private const NOT_IN_UPSTREAM_FONT = [];

    /**
     * Strings the x-text / 'icon' => scans pick up that are not icon names at
     * all (e.g. x-text="p.kind === 'deal' ? 'sell' : 'image'" yields 'deal';
     * a sort-column map yields 'icon' => 'icon').
     */
    private const NOT_ICONS = ['asc', 'deal', 'icon', 'sending', 'sent'];

    public function test_every_icon_used_in_a_view_is_present_in_the_subsetted_font(): void
    {
        $manifest = public_path('fonts/material-symbols-icons.txt');

        $this->assertFileExists(
            $manifest,
            'The icon manifest is missing. Run: python tools/subset-icon-font.py'
        );

        $subsetted = collect(explode("\n", (string) file_get_contents($manifest)))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->all();

        $used = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $content = (string) file_get_contents($file->getPathname());
            $found = [];

            // Same patterns as tools/subset-icon-font.py — keep them in sync.
            // Icon names may carry digits (inventory_2) — always [a-z0-9_].
            // 1. Literal ligature text inside an icon element: >shopping_cart<
            preg_match_all('~material-symbols-outlined[^>]*>\s*([a-z0-9_]+)\s*<~', $content, $matches);
            $found = $matches[1];

            // 2. Icons passed as blade component props: icon="shopping_basket"
            preg_match_all('~\bicon="([a-z0-9_]+)"~', $content, $matches);
            $found = array_merge($found, $matches[1]);

            // 3. Icons in PHP arrays inside views: 'icon' => 'dashboard'
            preg_match_all("~'icon'\s*=>\s*'([a-z0-9_]+)'~", $content, $matches);
            $found = array_merge($found, $matches[1]);

            // 4. Icons chosen at runtime by Alpine: x-text="show ? 'a' : 'b'"
            preg_match_all('~material-symbols-outlined[^>]*x-text="([^"]+)"~', $content, $matches);
            foreach ($matches[1] as $expr) {
                preg_match_all("~'([a-z0-9_]{2,})'~", $expr, $quoted);
                $found = array_merge($found, $quoted[1]);
            }

            // 5. Icons no scanner can see (PHP maps), declared where they are
            //    defined: icon-font: devices ac_unit mode_fan ...
            preg_match_all('~icon-font:\s*([a-z_][a-z0-9_ ]*)~', $content, $matches);
            foreach ($matches[1] as $decl) {
                $found = array_merge($found, preg_split('~ +~', trim($decl)));
            }

            foreach ($found as $icon) {
                $used[$icon] ??= str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        // Sidebar nav, docs manifest and settings/report groups define their
        // icons in PHP arrays outside resources/views — scan those too.
        $phpFiles = new \AppendIterator();
        foreach ([config_path(), app_path()] as $dir) {
            $phpFiles->append(new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            ));
        }

        foreach ($phpFiles as $file) {
            if (! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            preg_match_all("~'icon'\s*=>\s*'([a-z0-9_]+)'~", (string) file_get_contents($file->getPathname()), $matches);

            foreach ($matches[1] as $icon) {
                $used[$icon] ??= str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        $this->assertNotEmpty($used, 'No icons were found in any view — the detection pattern has drifted.');

        $missing = array_diff_key(
            $used,
            array_flip($subsetted),
            array_flip(self::NOT_IN_UPSTREAM_FONT),
            array_flip(self::NOT_ICONS)
        );

        $this->assertSame([], $missing, sprintf(
            "These icons are used in views but are not in the subsetted font, so they will render as their own name in the page:\n  %s\nRun: python tools/subset-icon-font.py",
            collect($missing)->map(fn ($where, $icon) => "{$icon}  ({$where})")->implode("\n  ")
        ));
    }
}
