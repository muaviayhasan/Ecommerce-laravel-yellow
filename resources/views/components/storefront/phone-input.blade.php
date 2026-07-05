@props(['value' => '', 'name' => 'phone', 'error' => 'phone'])
@php
    // Seed the editable part from a stored value: strip non-digits and a leading "03".
    $digits = preg_replace('/\D/', '', (string) $value);
    $seed = \Illuminate\Support\Str::startsWith($digits, '03') ? substr($digits, 2) : $digits;
@endphp
<div x-data="phoneField(@js($seed))">
    <div class="flex rounded-lg border border-outline-variant overflow-hidden focus-within:border-primary focus-within:ring-1 focus-within:ring-primary transition-colors @error($error) border-error @enderror">
        <span class="grid place-items-center px-3 bg-surface-container-low text-on-surface-variant font-semibold border-r border-outline-variant select-none">03</span>
        <input type="tel" inputmode="numeric" x-model="rest" @input="onInput" maxlength="10" placeholder="00-0000000"
            class="flex-1 min-w-0 px-3 py-2.5 outline-none bg-transparent" autocomplete="tel-national">
    </div>
    <input type="hidden" name="{{ $name }}" :value="full">
    @error($error)<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
</div>

@once
    @push('scripts')
        <script>
            // Pakistani mobile: fixed "03" prefix + 9 editable digits shown as "00-0000000".
            function phoneField(seed) {
                const fmt = (v) => {
                    const d = (v || '').replace(/\D/g, '').slice(0, 9);
                    return d.length > 2 ? d.slice(0, 2) + '-' + d.slice(2) : d;
                };
                return {
                    rest: fmt(seed),
                    onInput() { this.rest = fmt(this.rest); },
                    get full() {
                        const d = this.rest.replace(/\D/g, '');
                        if (!d) return '';
                        return d.length > 2 ? '03' + d.slice(0, 2) + '-' + d.slice(2) : '03' + d;
                    },
                };
            }
        </script>
    @endpush
@endonce
