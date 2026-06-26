@php $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none'; @endphp

<div x-data="roleMatrix()" class="space-y-6">
    <x-settings.section title="Role">
        <div class="max-w-md space-y-1.5">
            <label class="block text-sm font-medium text-on-surface-variant">Name <span class="text-error">*</span></label>
            <input type="text" name="name" value="{{ old('name', $role->name) }}" maxlength="100" placeholder="e.g. Warehouse staff" class="{{ $cell }}">
            @error('name')<p class="text-xs text-error">{{ $message }}</p>@enderror
        </div>
    </x-settings.section>

    <x-settings.section title="Permissions">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-on-surface-variant">Tick what this role may do. Group headers toggle a whole resource.</p>
            <div class="flex items-center gap-2">
                <button type="button" @click="selectAll(true)" class="px-3 py-1.5 text-xs font-semibold text-primary border border-outline-variant rounded-lg hover:bg-surface-container-high">Select all</button>
                <button type="button" @click="selectAll(false)" class="px-3 py-1.5 text-xs font-semibold text-on-surface-variant border border-outline-variant rounded-lg hover:bg-surface-container-high">Clear</button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($groups as $resource => $group)
                <div data-perm-group class="border border-outline-variant/60 rounded-xl p-4">
                    <label class="flex items-center gap-2 pb-2 mb-2 border-b border-outline-variant/40 cursor-pointer">
                        <input type="checkbox" class="group-toggle w-4 h-4 rounded accent-primary" @change="toggleGroup($event)">
                        <span class="text-sm font-bold text-on-surface">{{ $group['label'] }}</span>
                    </label>
                    <div class="space-y-1.5">
                        @foreach ($group['permissions'] as $perm)
                            <label class="flex items-center gap-2 text-sm text-on-surface-variant hover:text-on-surface cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="{{ $perm['name'] }}" @change="syncHeader($event)"
                                    @checked(in_array($perm['name'], old('permissions', $assigned), true))
                                    class="perm w-4 h-4 rounded accent-primary">
                                <span class="capitalize">{{ $perm['action'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </x-settings.section>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('roleMatrix', () => ({
                init() { this.$nextTick(() => this.syncAllHeaders()); },
                toggleGroup(e) {
                    e.target.closest('[data-perm-group]').querySelectorAll('input.perm').forEach(c => { c.checked = e.target.checked; });
                },
                syncHeader(e) {
                    const group = e.target.closest('[data-perm-group]');
                    const perms = [...group.querySelectorAll('input.perm')];
                    const header = group.querySelector('input.group-toggle');
                    if (header) header.checked = perms.length > 0 && perms.every(c => c.checked);
                },
                syncAllHeaders() {
                    this.$root.querySelectorAll('[data-perm-group]').forEach(group => {
                        const perms = [...group.querySelectorAll('input.perm')];
                        const header = group.querySelector('input.group-toggle');
                        if (header) header.checked = perms.length > 0 && perms.every(c => c.checked);
                    });
                },
                selectAll(value) {
                    this.$root.querySelectorAll('input.perm, input.group-toggle').forEach(c => { c.checked = value; });
                },
            }));
        });
    </script>
@endpush
