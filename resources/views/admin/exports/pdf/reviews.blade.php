<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reviews</title>
    @include('admin.exports.pdf._style')
</head>
<body>
    @include('admin.exports.pdf._watermark')
    <div class="doc-header">
        <h1>{{ config('app.name') }} — Product reviews</h1>
        <p class="meta">Generated {{ $generatedAt->format('d M Y, h:i A') }} · {{ $reviews->count() }} reviews</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Reviewer</th>
                <th class="num">Rating</th>
                <th>Review</th>
                <th>Verified</th>
                <th>Approved</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reviews as $r)
                <tr>
                    <td>{{ $r->product?->name ?? '—' }}</td>
                    <td>{{ $r->user?->name ?? 'Customer' }}</td>
                    <td class="num">{{ $r->rating }}/5</td>
                    <td>
                        @if ($r->title)<strong>{{ $r->title }}</strong><br>@endif
                        <span class="muted">{{ \Illuminate\Support\Str::limit($r->body, 160) }}</span>
                    </td>
                    <td><span class="badge {{ $r->verified_purchase ? 'on' : 'off' }}">{{ $r->verified_purchase ? 'Yes' : 'No' }}</span></td>
                    <td><span class="badge {{ $r->is_approved ? 'on' : 'off' }}">{{ $r->is_approved ? 'Yes' : 'Pending' }}</span></td>
                    <td>{{ format_date($r->created_at) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
