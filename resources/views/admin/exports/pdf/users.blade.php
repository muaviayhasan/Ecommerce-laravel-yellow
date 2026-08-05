<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Users</title>
    @include('admin.exports.pdf._style')
</head>
<body>
    @include('admin.exports.pdf._watermark')
    <div class="doc-header">
        <h1>{{ config('app.name') }} — Users</h1>
        <p class="meta">Generated {{ $generatedAt->format('d M Y, h:i A') }} · {{ $users->count() }} users</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Roles</th>
                <th>Status</th>
                <th>Last login</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $i => $u)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->phone ?? '—' }}</td>
                    <td>{{ $u->roles->pluck('name')->implode(', ') ?: '—' }}</td>
                    <td><span class="badge {{ $u->is_active ? 'on' : 'off' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="muted">{{ $u->last_login_at?->format('d M Y') ?? 'Never' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
