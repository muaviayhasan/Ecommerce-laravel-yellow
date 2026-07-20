@props([
    'billType' => 'a4',   // a4 | thermal
    'title' => 'Document',
    'back' => null,       // URL for the Close button
])
{{-- Shared print shell: renders the A4 / thermal page frame, a screen-only toolbar,
     and auto-opens the print dialog. The document content (invoice or receipt) is
     the slot; wrap the two designs with @if ($billType === 'thermal'). --}}
@php
    // A4 bills carry a faint centred brand watermark — the favicon for now,
    // falling back to the site logo (Admin → Settings → General).
    $watermark = favicon_url() ?: logo_url();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        @if ($billType === 'thermal')
            @page { size: 80mm auto; margin: 0; }
        @else
            @page { size: A4; margin: 14mm; }
        @endif

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #f3f4f6; color: #111827; }
        body { font-family: ui-sans-serif, system-ui, 'Segoe UI', Roboto, Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        a { color: inherit; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #6b7280; }
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: 700; }

        /* Screen toolbar (never printed) */
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; gap: 8px; justify-content: center; padding: 12px; background: #1f2937; }
        .toolbar button, .toolbar a { font: inherit; font-size: 14px; font-weight: 600; padding: 8px 18px; border-radius: 8px; border: 0; cursor: pointer; text-decoration: none; }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-close { background: #374151; color: #e5e7eb; }
        @media print { .toolbar { display: none !important; } html, body { background: #fff; } }

        /* ===== A4 invoice ===== */
        .a4 .sheet { max-width: 760px; margin: 16px auto; background: #fff; padding: 40px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .a4 .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; padding-bottom: 20px; border-bottom: 2px solid #111827; }
        .a4 .brand { font-size: 24px; font-weight: 800; letter-spacing: -.01em; }
        .a4 .doc-title { font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .a4 .parties { display: flex; justify-content: space-between; gap: 24px; margin: 24px 0; }
        .a4 .label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; color: #6b7280; margin-bottom: 4px; }
        .a4 table.items th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding: 8px 6px; }
        .a4 table.items td { padding: 10px 6px; border-bottom: 1px solid #f3f4f6; font-size: 13px; vertical-align: top; }
        .a4 .totals { margin-left: auto; width: 280px; margin-top: 18px; }
        .a4 .totals td { padding: 5px 0; font-size: 13px; }
        .a4 .totals .grand td { border-top: 2px solid #111827; font-size: 15px; font-weight: 800; padding-top: 10px; }
        .a4 .footer { margin-top: 36px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; white-space: pre-line; }
        .a4 .sheet { position: relative; }

        /* Rubber-stamp payment badge (top right, slightly tilted). */
        .a4 .pay-stamp {
            position: absolute;
            top: 200px;
            right: 44px;
            transform: rotate(-12deg);
            border: 4px double currentColor;
            border-radius: 12px;
            padding: 7px 20px;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
            opacity: .85;
            pointer-events: none;
        }
        .a4 .pay-stamp.paid { color: #15803d; }
        .a4 .pay-stamp.partial { color: #b45309; }
        .a4 .pay-stamp.unpaid { color: #b91c1c; }
        .a4 .pay-stamp.refunded { color: #6b7280; }

        @if ($billType !== 'thermal' && $watermark)
            /* Brand watermark — centred and faint; fixed while printing so it
               repeats on every page of multi-page invoices. */
            .a4 .sheet::before {
                content: '';
                position: absolute;
                inset: 0;
                background: url('{{ $watermark }}') no-repeat center center;
                background-size: min(480px, 80%);
                opacity: .05;
                pointer-events: none;
            }
            @media print {
                .a4 .sheet::before { position: fixed; inset: 0; }
            }
        @endif

        /* ===== Thermal 80mm receipt ===== */
        .thermal .sheet { width: 80mm; margin: 0 auto; background: #fff; padding: 4mm 4mm 6mm; font-family: 'Courier New', ui-monospace, monospace; font-size: 12px; line-height: 1.5; color: #000; }
        .thermal .brand { font-size: 16px; font-weight: 700; letter-spacing: .02em; }
        .thermal .doc-title { font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; margin-top: 2px; }
        .thermal hr { border: 0; border-top: 1px dashed #000; margin: 7px 0; }
        .thermal .row { display: flex; justify-content: space-between; gap: 8px; }
        .thermal .row.tight { line-height: 1.35; }
        .thermal .item { margin-top: 5px; }
        .thermal .item-name { font-weight: 700; }
        .thermal .grand { font-weight: 700; font-size: 14px; }
        .thermal .foot { margin-top: 10px; text-align: center; white-space: pre-line; }
        .thermal .barcode { margin-top: 8px; text-align: center; font-family: 'Courier New', monospace; letter-spacing: 2px; font-size: 11px; }
    </style>
</head>
<body class="{{ $billType }}">
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()">Print</button>
        @if ($back)<a class="btn-close" href="{{ $back }}">Close</a>@endif
    </div>

    {{ $slot }}

    <script>
        // Auto-open the print dialog once the page has rendered.
        window.addEventListener('load', () => setTimeout(() => window.print(), 350));
    </script>
</body>
</html>
