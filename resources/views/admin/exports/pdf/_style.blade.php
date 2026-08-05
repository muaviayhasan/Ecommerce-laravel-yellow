{{-- Shared inline styles for dompdf export documents (dompdf reads no external CSS). --}}
<style>
    * { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
    body { font-size: 9px; color: #1a1a1a; padding: 24px; }
    .doc-header { margin-bottom: 14px; border-bottom: 2px solid #f2b400; padding-bottom: 8px; }
    .doc-header h1 { font-size: 15px; }
    .doc-header .meta { font-size: 8px; color: #666; margin-top: 3px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #fdf3d0; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.4px; }
    th, td { border: 1px solid #ddd; padding: 4px 6px; vertical-align: top; }
    tr:nth-child(even) td { background: #fafafa; }
    .num { text-align: right; white-space: nowrap; }
    .muted { color: #888; }
    .badge { font-size: 8px; font-weight: bold; }
    .badge.on { color: #1a7f37; }
    .badge.off { color: #b42318; }
</style>
