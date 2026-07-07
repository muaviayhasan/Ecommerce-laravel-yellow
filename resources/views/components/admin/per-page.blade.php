@props(['perPage' => 15])

{{-- Drop inside a filter <form method="GET">. The hidden inputs keep the active
     sort when the form submits; the select changes rows-per-page and auto-submits. --}}
<input type="hidden" name="sort" value="{{ request('sort') }}">
<input type="hidden" name="dir" value="{{ request('dir') }}">
@php $ppOptions = collect([15, 25, 50, 100])->push((int) $perPage)->unique()->sort()->values(); @endphp
<select name="per_page" onchange="this.form.submit()" title="Rows per page"
    {{ $attributes->merge(['class' => 'ml-auto bg-surface-container-low border border-outline-variant/40 rounded-lg px-3 py-2 text-sm text-on-surface focus:ring-1 focus:ring-primary outline-none cursor-pointer']) }}>
    @foreach ($ppOptions as $n)
        <option value="{{ $n }}" @selected($n === (int) $perPage)>{{ $n }} / page</option>
    @endforeach
</select>
