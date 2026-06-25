@php
    $banners = [
        ['key' => 'status', 'tone' => 'bg-secondary-container text-on-secondary-container', 'icon' => 'check_circle'],
        ['key' => 'error', 'tone' => 'bg-error-container text-on-error-container', 'icon' => 'error'],
    ];
@endphp

@foreach ($banners as $banner)
    @if (session($banner['key']))
        <div class="flex items-center gap-2 {{ $banner['tone'] }} px-4 py-2.5 rounded-lg text-sm font-medium"
            x-data x-init="setTimeout(() => $el.remove(), 5000)">
            <span class="material-symbols-outlined text-[18px]">{{ $banner['icon'] }}</span>
            {{ session($banner['key']) }}
        </div>
    @endif
@endforeach
