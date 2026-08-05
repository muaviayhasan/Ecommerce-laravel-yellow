<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Blog posts</title>
    @include('admin.exports.pdf._style')
</head>
<body>
    @include('admin.exports.pdf._watermark')
    <div class="doc-header">
        <h1>{{ config('app.name') }} — Blog posts</h1>
        <p class="meta">Generated {{ $generatedAt->format('d M Y, h:i A') }} · {{ $posts->count() }} posts</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th>Author</th>
                <th class="num">Categories</th>
                <th>Status</th>
                <th>Published</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($posts as $p)
                <tr>
                    <td>{{ $p->title }}</td>
                    <td class="muted">{{ $p->slug }}</td>
                    <td>{{ $p->author?->name ?? '—' }}</td>
                    <td class="num">{{ $p->categories_count }}</td>
                    <td><span class="badge {{ $p->status === 'published' ? 'on' : 'off' }}">{{ ucfirst($p->status) }}</span></td>
                    <td>{{ $p->published_at ? format_date($p->published_at) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
