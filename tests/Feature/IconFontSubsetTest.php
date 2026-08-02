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

    /** Icons that are not in the upstream font at all, so subsetting cannot help. */
    private const NOT_IN_UPSTREAM_FONT = ['auto_fix_high', 'location_on', 'shortcut'];

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

            preg_match_all(
                '~material-symbols-outlined[^>]*>\s*([a-z_]+)\s*<~',
                (string) file_get_contents($file->getPathname()),
                $matches
            );

            foreach ($matches[1] as $icon) {
                $used[$icon] ??= str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        $this->assertNotEmpty($used, 'No icons were found in any view — the detection pattern has drifted.');

        $missing = array_diff_key(
            $used,
            array_flip($subsetted),
            array_flip(self::NOT_IN_UPSTREAM_FONT)
        );

        $this->assertSame([], $missing, sprintf(
            "These icons are used in views but are not in the subsetted font, so they will render as their own name in the page:\n  %s\nRun: python tools/subset-icon-font.py",
            collect($missing)->map(fn ($where, $icon) => "{$icon}  ({$where})")->implode("\n  ")
        ));
    }
}
