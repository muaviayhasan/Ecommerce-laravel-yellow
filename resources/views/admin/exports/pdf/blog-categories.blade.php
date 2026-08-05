<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Blog categories</title>
    @include('admin.exports.pdf._style')
</head>
<body>
    @include('admin.exports.pdf._watermark')
    <div class="doc-header">
        <h1>{{ config('app.name') }} — Blog categories</h1>
        <p class="meta">Generated {{ $generatedAt->format('d M Y, h:i A') }} · {{ $categories->count() }} categories</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Parent</th>
                <th class="num">Posts</th>
                <th class="num">Sort</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $i => $c)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $c->name }}</td>
                    <td class="muted">{{ $c->slug }}</td>
                    <td>{{ $c->parent?->name ?? '—' }}</td>
                    <td class="num">{{ $c->posts_count }}</td>
                    <td class="num">{{ $c->sort_order }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
