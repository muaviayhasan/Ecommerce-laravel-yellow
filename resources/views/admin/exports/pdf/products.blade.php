<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Products</title>
    @include('admin.exports.pdf._style')
</head>
<body>
    @include('admin.exports.pdf._watermark')
    <div class="doc-header">
        <h1>{{ config('app.name') }} — Products</h1>
        <p class="meta">Generated {{ $generatedAt->format('d M Y, h:i A') }} · {{ $products->count() }} products / {{ $products->sum(fn ($p) => $p->variants->count()) }} variants</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Product SKU</th>
                <th>Name</th>
                <th>Category</th>
                <th>Mode</th>
                <th>Variant SKU</th>
                <th>Attributes</th>
                <th class="num">Retail price</th>
                <th class="num">Stock</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $p)
                @foreach ($p->variants->sortBy('id')->values() as $v)
                    <tr>
                        <td>{{ $p->sku }}</td>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->category?->name ?? '—' }}</td>
                        <td>{{ $p->variant_mode }}</td>
                        <td>{{ $v->sku }}</td>
                        <td class="muted">{{ \App\Services\ImportExport\Exporters\ProductExporter::attributesCell($v) ?: '—' }}</td>
                        <td class="num">{{ format_money($v->retail_price) }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) $v->stock_quantity, 3, '.', ''), '0'), '.') }}</td>
                        <td><span class="badge {{ ($p->is_active && $v->is_active) ? 'on' : 'off' }}">{{ ($p->is_active && $v->is_active) ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
