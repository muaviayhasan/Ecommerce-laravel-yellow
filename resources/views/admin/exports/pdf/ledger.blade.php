<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ledger</title>
    @include('admin.exports.pdf._style')
</head>
<body>
    @include('admin.exports.pdf._watermark')
    <div class="doc-header">
        <h1>{{ config('app.name') }} — Ledger</h1>
        <p class="meta">Generated {{ $generatedAt->format('d M Y, h:i A') }} · {{ $entries->count() }} entries</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Account</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th>Memo</th>
                <th>Reference</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($entries as $e)
                <tr>
                    <td>{{ format_date($e->entry_date) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $e->account)) }}</td>
                    <td class="num">{{ (float) $e->debit > 0 ? format_money($e->debit) : '—' }}</td>
                    <td class="num">{{ (float) $e->credit > 0 ? format_money($e->credit) : '—' }}</td>
                    <td>{{ $e->memo }}</td>
                    <td class="muted">{{ \App\Services\ImportExport\Exporters\LedgerExporter::referenceLabel($e) ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" style="text-align: right;">Totals</th>
                <th class="num">{{ format_money($totals['debit']) }}</th>
                <th class="num">{{ format_money($totals['credit']) }}</th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
