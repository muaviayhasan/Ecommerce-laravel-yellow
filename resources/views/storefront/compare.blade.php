@extends('layouts.storefront')
@section('robots', 'noindex, follow')

@section('title', 'Compare — ' . config('app.name'))

@section('content')
    <div class="bg-background py-8">
        <div class="app-container">
            <nav class="flex items-center gap-2 text-label-sm text-on-surface-variant mb-8" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-on-surface">Compare</span>
            </nav>

            <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
                <h1 class="text-3xl sm:text-4xl font-light">Compare Products</h1>
                @if ($rows->isNotEmpty())
                    <form method="POST" action="{{ route('compare.clear') }}">@csrf @method('DELETE')
                        <button type="submit" class="text-on-surface-variant font-bold hover:text-error transition-colors text-label-sm">Clear all</button>
                    </form>
                @endif
            </div>

            @if (session('status') || session('error'))
                <div class="mb-6 p-4 rounded-lg flex items-center gap-2 {{ session('error') ? 'bg-error-container/40' : 'bg-secondary-container/40' }} text-on-surface">
                    <span class="material-symbols-outlined {{ session('error') ? 'text-error' : 'text-secondary' }}">{{ session('error') ? 'error' : 'check_circle' }}</span>
                    {{ session('error') ?? session('status') }}
                </div>
            @endif

            @if ($rows->isEmpty())
                <div class="bg-white rounded-lg border border-outline-variant p-16 text-center">
                    <span class="material-symbols-outlined text-gray-300" style="font-size:72px;">sync</span>
                    <p class="mt-4 text-xl font-light text-on-surface-variant">No products to compare yet.</p>
                    <p class="text-on-surface-variant text-label-sm mt-1">Add products from the shop (up to 4) to see them side by side.</p>
                    <a href="{{ route('shop') }}" class="inline-block mt-6 bg-primary-container text-on-primary-container px-8 py-3 font-bold rounded hover:brightness-95 transition-all">Browse products</a>
                </div>
            @else
                <div class="bg-white border border-outline-variant rounded-lg overflow-x-auto">
                    <table class="w-full text-left min-w-[640px]">
                        <tbody class="divide-y divide-outline-variant">
                            {{-- Product heads --}}
                            <tr>
                                <th class="p-4 w-40 align-top text-on-surface-variant font-medium"></th>
                                @foreach ($rows as $row)
                                    <td class="p-4 align-top border-l border-outline-variant" style="width: {{ 80 / max(1, $rows->count()) }}%">
                                        <div class="flex justify-between items-start gap-2 mb-3">
                                            <a href="{{ $row['url'] }}" class="block w-24 h-24 bg-white"><img src="{{ $row['image'] }}" alt="{{ $row['name'] }}" class="w-full h-full object-contain"></a>
                                            <form method="POST" action="{{ route('compare.remove', $row['slug']) }}">@csrf @method('DELETE')
                                                <button type="submit" aria-label="Remove" class="p-1 text-on-surface-variant hover:text-error"><span class="material-symbols-outlined text-[18px]">close</span></button>
                                            </form>
                                        </div>
                                        <a href="{{ $row['url'] }}" class="font-bold hover:text-primary transition-colors line-clamp-2">{{ $row['name'] }}</a>
                                    </td>
                                @endforeach
                            </tr>
                            @php
                                $attr = function (string $label, callable $cell) use ($rows) {
                                    return ['label' => $label, 'cell' => $cell];
                                };
                                $attributes = [
                                    ['Price', fn ($r) => 'Rs ' . number_format($r['price'])],
                                    ['Availability', fn ($r) => $r['availability']],
                                    ['Category', fn ($r) => $r['category']],
                                    ['SKU', fn ($r) => $r['sku']],
                                ];
                            @endphp
                            @foreach ($attributes as [$label, $cell])
                                <tr>
                                    <th class="p-4 align-top text-on-surface-variant font-medium bg-surface-container-low/40">{{ $label }}</th>
                                    @foreach ($rows as $row)
                                        <td class="p-4 align-top border-l border-outline-variant {{ $label === 'Price' ? 'font-bold' : 'text-on-surface-variant' }}">{{ $cell($row) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr>
                                <th class="p-4 align-top text-on-surface-variant font-medium bg-surface-container-low/40">Key features</th>
                                @foreach ($rows as $row)
                                    <td class="p-4 align-top border-l border-outline-variant text-on-surface-variant text-label-sm">
                                        @if (! empty($row['highlights']))
                                            <ul class="list-disc list-inside space-y-1">@foreach (array_slice($row['highlights'], 0, 6) as $h)<li>{{ $h }}</li>@endforeach</ul>
                                        @else &mdash; @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <th class="p-4 bg-surface-container-low/40"></th>
                                @foreach ($rows as $row)
                                    <td class="p-4 align-top border-l border-outline-variant">
                                        @if ($row['variant_id'])
                                            <form method="POST" action="{{ route('cart.add') }}">
                                                @csrf
                                                <input type="hidden" name="variant_id" value="{{ $row['variant_id'] }}">
                                                <button type="submit" class="bg-primary-container text-on-primary-container px-4 py-2 rounded text-label-sm font-bold hover:brightness-95 transition-all flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">shopping_cart</span> Add</button>
                                            </form>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
