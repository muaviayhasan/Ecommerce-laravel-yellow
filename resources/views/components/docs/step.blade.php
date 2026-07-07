@props(['title', 'num' => null])

<li class="ml-6">
    <span class="absolute -left-[13px] flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white text-[12px] font-bold ring-4 ring-background">
        {{ $num }}
    </span>
    <p class="font-semibold text-on-surface mb-1">{{ $title }}</p>
    <div class="text-sm text-on-surface-variant leading-relaxed [&_code]:font-mono [&_code]:text-[12px] [&_code]:bg-surface-container-high [&_code]:px-1 [&_code]:rounded [&_strong]:text-on-surface">
        {{ $slot }}
    </div>
</li>
