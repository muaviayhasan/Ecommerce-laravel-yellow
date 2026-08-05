"""
Rebuild public/fonts/material-symbols-outlined.woff2 so it carries only the icons
this project actually uses.

    python tools/subset-icon-font.py

Why this exists
---------------
The full Material Symbols font is 313 KB for ~3,300 icons. This site uses about
130. On a throttled mobile connection that font was the single heaviest thing on
the critical path — sixteen times the weight of the LCP image it was queued ahead
of. Subsetted, it is ~11 KB.

Why it is not a one-line pyftsubset call
----------------------------------------
Material Symbols renders "shopping_cart" through a GSUB ligature whose input is
the letters s,h,o,p,... Those letters must be retained or no ligature can fire —
but retaining them makes every one of the 3,300 icon ligatures reachable, so
fontTools' layout closure keeps the whole font (313 KB -> 243 KB, barely a win).

Turning the closure off (--no-layout-closure) instead strips every ligature rule,
and each icon then renders as the literal text "shopping_cart" — worse than
useless, and it looks perfectly fine in a file listing, which is how you ship it
by accident.

So the ligature table is pruned to the icons in use FIRST, and the closure is
then allowed to run normally: it can only reach what is left.

One more trap: this font's lookups are LookupType 7 (Extension), which wrap the
real subtable. Walking lookup.SubTable directly finds no ligatures at all and
silently prunes nothing.

After running this
------------------
Commit both the font and public/fonts/material-symbols-icons.txt. The manifest is
what IconFontSubsetTest checks against, so a newly added icon fails a test rather
than shipping as a word on the page.

Requires: python -m pip install fonttools brotli
"""

import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
VIEWS = ROOT / "resources" / "views"
FONT = ROOT / "public" / "fonts" / "material-symbols-outlined.woff2"
MANIFEST = ROOT / "public" / "fonts" / "material-symbols-icons.txt"
SOURCE = ROOT / "tools" / "material-symbols-outlined.full.woff2"

# Icon names may carry digits (inventory_2, forward_10) — always [a-z0-9_].
# Literal ligature text inside an icon element: >shopping_cart<
ICON_RE = re.compile(r"material-symbols-outlined[^>]*>\s*([a-z0-9_]+)\s*<")
# Icons chosen at runtime by Alpine, e.g. x-text="show ? 'visibility_off' : 'visibility'".
# Every quoted token in the attribute is collected; strings that are not icon
# names get filtered out later against the font's real glyph list.
XTEXT_RE = re.compile(r"material-symbols-outlined[^>]*x-text=\"([^\"]+)\"")
QUOTED_RE = re.compile(r"'([a-z0-9_]{2,})'")
# Icons passed as blade component props: <x-admin.stat-card icon="shopping_basket">
ATTR_RE = re.compile(r"\bicon=\"([a-z0-9_]+)\"")
# Icons in PHP arrays — sidebar nav (config/navigation.php), docs manifest,
# settings/report groups in controllers: 'icon' => 'dashboard'
PHP_RE = re.compile(r"'icon'\s*=>\s*'([a-z0-9_]+)'")
# Icons no scanner can see (values of a PHP map, picked by slug at runtime) are
# declared next to their definition:  icon-font: devices ac_unit mode_fan ...
DECL_RE = re.compile(r"icon-font:\s*([a-z_][a-z0-9_ ]*)")

# tests/Feature/IconFontSubsetTest.php runs the same patterns over the same
# directories — keep them in sync.


def icons_used():
    found = set()
    for path in VIEWS.rglob("*.blade.php"):
        text = path.read_text(encoding="utf-8", errors="ignore")
        found.update(ICON_RE.findall(text))
        found.update(ATTR_RE.findall(text))
        found.update(PHP_RE.findall(text))
        for attr in XTEXT_RE.findall(text):
            found.update(QUOTED_RE.findall(attr))
        for decl in DECL_RE.findall(text):
            found.update(decl.split())
    for root in (ROOT / "config", ROOT / "app"):
        for path in root.rglob("*.php"):
            found.update(PHP_RE.findall(path.read_text(encoding="utf-8", errors="ignore")))
    return found


def main():
    if not SOURCE.exists():
        print("Missing the full font at " + str(SOURCE) + ".")
        print("Download Material Symbols Outlined (static, opsz 24 / wght 400) and save it there.")
        return 1

    from fontTools.ttLib import TTFont

    work = ROOT / "tools" / "_subset_work"
    work.mkdir(exist_ok=True)
    full_ttf = work / "full.ttf"
    pruned = work / "pruned.ttf"

    subprocess.run(
        [sys.executable, "-m", "fontTools.ttLib.woff2", "decompress", str(SOURCE), "-o", str(full_ttf)],
        check=True, capture_output=True,
    )

    font = TTFont(full_ttf)
    present = set(font.getGlyphOrder())
    wanted = icons_used()
    keep = wanted & present
    unavailable = sorted(wanted - present)

    def subtables(lookup):
        # LookupType 7 wraps the real subtable; walking .SubTable finds nothing.
        for sub in lookup.SubTable:
            yield getattr(sub, "ExtSubTable", sub)

    kept = dropped = 0
    for lookup in font["GSUB"].table.LookupList.Lookup:
        for sub in subtables(lookup):
            if not hasattr(sub, "ligatures"):
                continue
            for first, ligs in list(sub.ligatures.items()):
                survivors = [lg for lg in ligs if lg.LigGlyph in keep]
                dropped += len(ligs) - len(survivors)
                kept += len(survivors)
                if survivors:
                    sub.ligatures[first] = survivors
                else:
                    del sub.ligatures[first]

    font.save(pruned)

    before = SOURCE.stat().st_size
    subprocess.run(
        [
            sys.executable, "-m", "fontTools.subset", str(pruned),
            "--text=" + " ".join(sorted(keep)),
            "--layout-features+=rlig,liga,dlig,calt",
            "--flavor=woff2",
            "--output-file=" + str(FONT),
        ],
        check=True, capture_output=True,
    )

    MANIFEST.write_text("\n".join(sorted(keep)) + "\n", encoding="utf-8")

    after = FONT.stat().st_size
    print("  icons used in views : " + str(len(wanted)))
    print("  ligatures kept      : " + str(kept) + " (dropped " + str(dropped) + ")")
    print("  font                : %.1f KB -> %.1f KB" % (before / 1024, after / 1024))
    if unavailable:
        print("  NOT in this font build, will render as text: " + ", ".join(unavailable))

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
