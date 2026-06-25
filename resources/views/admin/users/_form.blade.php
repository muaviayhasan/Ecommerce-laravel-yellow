@php
    $account = [
        'name' => ['input' => 'text', 'label' => 'Name', 'max' => 255],
        'email' => ['input' => 'email', 'label' => 'Email', 'max' => 255],
        'phone' => ['input' => 'text', 'label' => 'Phone', 'max' => 30],
        'is_active' => ['input' => 'toggle', 'label' => 'Active', 'help' => 'Disabled users can\'t sign in.'],
    ];
    $checkedRoles = old('roles', $assigned);
@endphp

<div class="space-y-6">
    {{-- Account --}}
    <x-settings.section title="Account">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
            @foreach ($account as $name => $meta)
                <div @class(['md:col-span-2' => ($meta['input'] ?? '') === 'toggle'])>
                    <x-settings.field group="user" :name="$name" :meta="$meta" :value="data_get($user, $name)" />
                </div>
            @endforeach
        </div>
    </x-settings.section>

    {{-- Password --}}
    <x-settings.section title="Password">
        <div class="md:max-w-md space-y-1.5" x-data="{ show: false }">
            <label for="user_password" class="block text-sm font-medium text-on-surface-variant">Password</label>
            <div class="relative">
                <input id="user_password" name="password" :type="show ? 'text' : 'password'" autocomplete="new-password" minlength="8"
                    placeholder="{{ $user->exists ? '•••••••• — leave blank to keep' : 'At least 8 characters' }}"
                    class="w-full bg-surface-container-low border border-outline-variant rounded-lg py-2.5 pl-4 pr-11 text-sm text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary focus:border-primary outline-none transition">
                <button type="button" @click="show = !show" tabindex="-1"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>
            @error('password')<p class="text-xs text-error flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">error</span>{{ $message }}</p>@enderror
            <p class="text-xs text-outline">{{ $user->exists ? 'Only fill this in to change the password.' : 'The user signs in with their email and this password.' }}</p>
        </div>
    </x-settings.section>

    {{-- Roles --}}
    <x-settings.section title="Roles" description="What this user can access. A user can have more than one role.">
        @error('roles.*')<p class="text-xs text-error mb-3 flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">error</span>{{ $message }}</p>@enderror
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach ($roles as $role)
                <label class="flex items-center gap-2.5 p-3 rounded-lg border border-outline-variant hover:bg-surface-container-low cursor-pointer transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary-container/10">
                    <input type="checkbox" name="roles[]" value="{{ $role }}" @checked(in_array($role, $checkedRoles, true))
                        class="w-4 h-4 accent-primary cursor-pointer shrink-0">
                    <span class="text-sm font-medium text-on-surface capitalize truncate">{{ str_replace('-', ' ', $role) }}</span>
                </label>
            @endforeach
        </div>
    </x-settings.section>
</div>
