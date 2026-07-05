@extends('layouts.storefront')

@section('title', 'My Addresses — ' . config('app.name'))
@section('hideNewsletter', '1')

@section('content')
    <x-storefront.account-shell active="addresses">
        <div x-data="addressBook()">
            {{-- Header --}}
            <div class="bg-white rounded-lg border border-outline-variant p-5 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold">Addresses</h1>
                    <p class="text-label-sm text-on-surface-variant">Manage your shipping &amp; billing addresses.</p>
                </div>
                <button type="button" @click="openCreate()"
                    class="inline-flex items-center gap-1.5 bg-primary-container text-on-primary-container px-4 py-2.5 rounded-full font-bold text-label-sm hover:brightness-105 transition shrink-0">
                    <span class="material-symbols-outlined text-[18px]">add</span> Add address
                </button>
            </div>

            {{-- List --}}
            @if ($addresses->isEmpty())
                <div class="bg-white rounded-lg border border-outline-variant p-16 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-gray-300" style="font-size:64px;">location_off</span>
                    <p class="mt-3 text-lg font-light">You have no saved addresses.</p>
                    <button type="button" @click="openCreate()" class="inline-block mt-5 bg-primary-container text-on-primary-container px-8 py-3 font-bold rounded-full hover:brightness-105 transition">Add your first address</button>
                </div>
            @else
                <div class="grid sm:grid-cols-2 gap-4">
                    @foreach ($addresses as $a)
                        @php $payload = ['id' => $a->id, 'label' => $a->label ?? '', 'name' => $a->name ?? '', 'phone' => $a->phone ?? '', 'line1' => $a->line1 ?? '', 'line2' => $a->line2 ?? '', 'city' => $a->city ?? '', 'state' => $a->state ?? '', 'zip' => $a->zip ?? '', 'country' => $a->country ?? '', 'is_default_billing' => (bool) $a->is_default_billing, 'is_default_shipping' => (bool) $a->is_default_shipping]; @endphp
                        <div class="bg-white rounded-lg border border-outline-variant p-5 flex flex-col">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                                    @if ($a->label)<span class="font-bold truncate">{{ $a->label }}</span>@endif
                                    @if ($a->is_default_shipping)<span class="text-[10px] font-bold uppercase tracking-wide bg-primary-container/40 text-on-primary-container px-2 py-0.5 rounded-full shrink-0">Shipping</span>@endif
                                    @if ($a->is_default_billing)<span class="text-[10px] font-bold uppercase tracking-wide bg-secondary-container/50 text-secondary px-2 py-0.5 rounded-full shrink-0">Billing</span>@endif
                                </div>
                            </div>
                            <p class="font-medium">{{ $a->name }}</p>
                            @if ($a->phone)<p class="text-body-base text-on-surface-variant">{{ $a->phone }}</p>@endif
                            <p class="text-body-base text-on-surface-variant mt-0.5">{{ collect([$a->line1, $a->line2, $a->city, $a->state, $a->zip, $a->country])->filter()->join(', ') }}</p>

                            <div class="flex items-center gap-2 mt-4 pt-3 border-t border-outline-variant/60">
                                <button type="button" @click='openEdit(@json($payload))' class="text-label-sm font-bold text-primary hover:underline flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">edit</span> Edit
                                </button>
                                <form method="POST" action="{{ route('account.addresses.destroy', $a) }}" @submit.prevent="if (confirm('Remove this address?')) $el.submit()" class="ms-auto">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-label-sm font-bold text-error hover:underline flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">delete</span> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Add / edit modal --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center" style="display:none;">
                <div class="absolute inset-0 bg-black/50" @click="close()"></div>
                <div x-show="open" x-transition
                    class="relative bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl max-h-[92vh] overflow-y-auto shadow-2xl">
                    <div class="sticky top-0 bg-white border-b border-outline-variant px-5 py-4 flex items-center justify-between">
                        <h2 class="font-bold text-lg" x-text="form.id ? 'Edit address' : 'Add address'"></h2>
                        <button type="button" @click="close()" class="text-on-surface-variant hover:text-on-surface"><span class="material-symbols-outlined">close</span></button>
                    </div>

                    <form method="POST" :action="form.id ? `{{ url('account/addresses') }}/${form.id}` : '{{ route('account.addresses.store') }}'" class="p-5 space-y-4">
                        @csrf
                        <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
                        <input type="hidden" name="address_id" :value="form.id">

                        <div>
                            <label class="block text-label-sm font-medium mb-1">Label <span class="text-on-surface-variant font-normal">(e.g. Home, Office)</span></label>
                            <input type="text" name="label" x-model="form.label" maxlength="60" class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-label-sm font-medium mb-1">Full name <span class="text-error">*</span></label>
                                <input type="text" name="name" x-model="form.name" required class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('name') border-error @enderror">
                                @error('name')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-label-sm font-medium mb-1">Phone</label>
                                <input type="text" name="phone" x-model="form.phone" class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-label-sm font-medium mb-1">Address line 1 <span class="text-error">*</span></label>
                            <input type="text" name="line1" x-model="form.line1" required class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('line1') border-error @enderror">
                            @error('line1')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-label-sm font-medium mb-1">Address line 2</label>
                            <input type="text" name="line2" x-model="form.line2" class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-label-sm font-medium mb-1">City <span class="text-error">*</span></label>
                                <input type="text" name="city" x-model="form.city" required class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('city') border-error @enderror">
                                @error('city')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-label-sm font-medium mb-1">State / Province</label>
                                <input type="text" name="state" x-model="form.state" class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-label-sm font-medium mb-1">ZIP / Postal code</label>
                                <input type="text" name="zip" x-model="form.zip" class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                            </div>
                            <div>
                                <label class="block text-label-sm font-medium mb-1">Country <span class="text-error">*</span></label>
                                <input type="text" name="country" x-model="form.country" required class="w-full rounded-lg border border-outline-variant px-3 py-2.5 focus:border-primary focus:ring-1 focus:ring-primary outline-none @error('country') border-error @enderror">
                                @error('country')<p class="text-error text-label-sm mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_default_shipping" value="1" x-model="form.is_default_shipping" class="rounded border-outline-variant text-primary focus:ring-primary">
                                <span class="text-body-base">Set as default shipping address</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_default_billing" value="1" x-model="form.is_default_billing" class="rounded border-outline-variant text-primary focus:ring-primary">
                                <span class="text-body-base">Set as default billing address</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit" class="flex-1 bg-primary-container text-on-primary-container py-3 rounded-full font-bold hover:brightness-105 transition">Save address</button>
                            <button type="button" @click="close()" class="px-5 py-3 rounded-full font-bold text-on-surface-variant hover:bg-surface-container transition">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </x-storefront.account-shell>

    @push('scripts')
        <script>
            function addressBook() {
                const blank = { id: '', label: '', name: @js(auth()->user()->name ?? ''), phone: @js(auth()->user()->phone ?? ''), line1: '', line2: '', city: '', state: '', zip: '', country: '', is_default_billing: false, is_default_shipping: false };
                return {
                    open: {{ $errors->any() ? 'true' : 'false' }},
                    form: @js([
                        'id' => old('address_id', ''),
                        'label' => old('label', ''),
                        'name' => old('name', ''),
                        'phone' => old('phone', ''),
                        'line1' => old('line1', ''),
                        'line2' => old('line2', ''),
                        'city' => old('city', ''),
                        'state' => old('state', ''),
                        'zip' => old('zip', ''),
                        'country' => old('country', ''),
                        'is_default_billing' => (bool) old('is_default_billing'),
                        'is_default_shipping' => (bool) old('is_default_shipping'),
                    ]),
                    openCreate() { this.form = { ...blank }; this.open = true; },
                    openEdit(a) { this.form = { ...blank, ...a }; this.open = true; },
                    close() { this.open = false; },
                };
            }
        </script>
    @endpush
@endsection
