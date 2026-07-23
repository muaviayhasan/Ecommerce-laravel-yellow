@extends('layouts.admin')

@section('title', 'Support')

@section('content')
    <div class="mb-2">
        <div class="flex items-center gap-2 text-label-sm mb-1">
            <a href="{{ route('admin.dashboard') }}" class="text-primary font-semibold hover:underline">Dashboard</a>
            <span class="material-symbols-outlined text-outline text-[16px]">chevron_right</span>
            <span class="text-on-surface-variant font-semibold">Support</span>
        </div>
        <h2 class="text-2xl font-bold text-on-surface">Support messages</h2>
    </div>

    <x-admin.panel class="!p-0 overflow-hidden">
        <div class="grid grid-cols-12 h-[72vh] min-h-[30rem]">
            {{-- Conversations --}}
            <div x-data="supportList({
                    seed: @js($conversationsData),
                    activeId: {{ $active?->id ?? 'null' }},
                    url: @js(route('admin.support.conversations')),
                    indexUrl: @js(route('admin.support.index')),
                    search: @js($filters['search'] ?? ''),
                    bulkUrl: @js(route('admin.support.bulk-delete')),
                })"
                class="col-span-12 md:col-span-4 xl:col-span-3 border-r border-outline-variant/60 flex flex-col min-h-0 {{ $explicit ? 'hidden md:flex' : '' }}">
                <div class="p-4 border-b border-outline-variant/60">
                    <form method="GET" class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search contacts…"
                            class="w-full pl-10 pr-3 py-2.5 bg-surface-container-low border border-outline-variant rounded-lg text-sm outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                    </form>

                    {{-- Inbox tools: select-to-delete, mark all read, cleanup menu --}}
                    @canany(['support.delete', 'support.reply'])
                        <div class="mt-3 flex flex-wrap items-stretch gap-2">
                            @can('support.delete')
                                <button type="button" @click="toggleMode()"
                                    :class="selectMode ? 'bg-primary text-on-primary border-primary' : 'text-on-surface-variant border-outline-variant hover:bg-surface-container-high'"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-lg border text-xs font-semibold whitespace-nowrap transition-colors">
                                    <span class="material-symbols-outlined text-[16px]" x-text="selectMode ? 'close' : 'checklist'"></span>
                                    <span x-text="selectMode ? 'Cancel' : 'Select'"></span>
                                </button>
                            @endcan
                            @can('support.reply')
                                <button type="button" title="Mark all messages as read"
                                    @click="window.__postForm(@js(route('admin.support.read-all')))"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-lg border border-outline-variant text-xs font-semibold text-on-surface-variant hover:bg-surface-container-high whitespace-nowrap transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">done_all</span> Read all
                                </button>
                            @endcan
                            @can('support.delete')
                                <div class="relative flex-1" x-data="{ open: false }" @click.outside="open = false">
                                    <button type="button" @click="open = !open" :class="open ? 'bg-surface-container-high' : ''"
                                        class="w-full h-full inline-flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-lg border border-outline-variant text-xs font-semibold text-on-surface-variant hover:bg-surface-container-high whitespace-nowrap transition-colors">
                                        <span class="material-symbols-outlined text-[16px]">delete_sweep</span> Cleanup
                                        <span class="material-symbols-outlined text-[16px] transition-transform" :class="open && 'rotate-180'">expand_more</span>
                                    </button>
                                    <div x-show="open" x-cloak x-transition
                                        class="absolute right-0 mt-1 w-72 z-30 bg-surface-container-lowest dark:bg-surface-container-high border border-outline-variant rounded-xl shadow-xl p-1.5">
                                        <p class="px-2.5 pt-1.5 pb-1 text-[10px] font-bold uppercase tracking-widest text-outline">Old messages</p>
                                        <button type="button"
                                            @click="open = false; $store.supportConfirm.ask('Delete messages older than 15 days?', 'Messages older than 15 days will be permanently removed; conversations left empty are cleaned up too.', () => window.__postForm(@js(route('admin.support.purge')), { mode: 'older', days: 15 }))"
                                            class="w-full flex items-center gap-2 px-2.5 py-2 rounded-lg text-sm text-on-surface hover:bg-surface-container-high text-left transition-colors">
                                            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">history</span> Older than 15 days
                                        </button>
                                        <button type="button"
                                            @click="open = false; $store.supportConfirm.ask('Delete messages older than 30 days?', 'Messages older than 30 days will be permanently removed; conversations left empty are cleaned up too.', () => window.__postForm(@js(route('admin.support.purge')), { mode: 'older', days: 30 }))"
                                            class="w-full flex items-center gap-2 px-2.5 py-2 rounded-lg text-sm text-on-surface hover:bg-surface-container-high text-left transition-colors">
                                            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">history</span> Older than 30 days
                                        </button>
                                        <p class="px-2.5 pt-2 pb-1 text-[10px] font-bold uppercase tracking-widest text-outline border-t border-outline-variant/60 mt-1.5">Automated messages</p>
                                        <button type="button"
                                            @click="open = false; $store.supportConfirm.ask('Delete all order-update messages?', 'Every automated order status message the system posted into chats will be removed. Customer and staff messages stay.', () => window.__postForm(@js(route('admin.support.purge')), { mode: 'bot' }))"
                                            class="w-full flex items-center gap-2 px-2.5 py-2 rounded-lg text-sm text-on-surface hover:bg-surface-container-high text-left transition-colors">
                                            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">smart_toy</span> Order-update messages
                                        </button>
                                    </div>
                                </div>
                            @endcan
                        </div>
                    @endcanany

                    {{-- Bulk-selection action bar --}}
                    <div x-show="selectMode && selected.length" x-cloak
                        class="mt-3 flex items-center justify-between gap-2 bg-error-container/40 border border-error/30 rounded-lg px-3 py-2">
                        <span class="text-xs font-semibold text-error" x-text="selected.length + ' selected'"></span>
                        <button type="button" @click="bulkDelete()"
                            class="inline-flex items-center gap-1 text-xs font-bold text-error hover:opacity-80 transition-opacity">
                            <span class="material-symbols-outlined text-[16px]">delete</span> Delete selected
                        </button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto divide-y divide-outline-variant/40">
                    <template x-for="c in conversations" :key="c.id">
                        <a :href="link(c.id)"
                            @click="if (selectMode) { $event.preventDefault(); toggle(c.id); }"
                            class="flex items-center gap-3 px-4 py-3.5 transition-colors"
                            :class="[c.id === activeId && ! selectMode ? 'bg-surface-container-high' : 'hover:bg-surface-container-low', selectMode && isSel(c.id) ? '!bg-primary/10' : '']">
                            <span x-show="selectMode" x-cloak
                                class="shrink-0 w-6 h-6 rounded-full border-2 grid place-items-center transition-colors"
                                :class="isSel(c.id) ? 'bg-primary border-primary text-on-primary' : 'border-outline'">
                                <span class="material-symbols-outlined text-[16px]" x-show="isSel(c.id)">check</span>
                            </span>
                            <div class="relative shrink-0">
                                <div class="w-11 h-11 rounded-full bg-primary/10 text-primary grid place-items-center font-bold" x-text="c.initial"></div>
                                <span x-show="c.online" x-cloak class="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-green-500 ring-2 ring-surface-container-lowest" title="Online"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-semibold text-on-surface flex items-center gap-1 min-w-0">
                                        <span class="truncate" x-text="c.name"></span>
                                        <template x-if="c.verified">
                                            <span class="material-symbols-outlined text-primary text-[16px] shrink-0" style="font-variation-settings:'FILL' 1;" title="Verified customer">verified</span>
                                        </template>
                                        <template x-if="c.blocked">
                                            <span class="material-symbols-outlined text-error text-[15px] shrink-0" title="Blocked">block</span>
                                        </template>
                                    </p>
                                    <span class="text-[10px] text-outline shrink-0" x-text="c.time"></span>
                                </div>
                                <div class="flex items-center justify-between gap-2 mt-0.5">
                                    <p class="text-xs text-outline truncate"><span x-show="c.from_admin">You: </span><span x-text="c.preview"></span></p>
                                    <template x-if="c.unread">
                                        <span class="min-w-5 h-5 px-1.5 rounded-full bg-primary text-on-primary text-[11px] font-bold grid place-items-center shrink-0" x-text="c.unread"></span>
                                    </template>
                                </div>
                            </div>
                        </a>
                    </template>
                    <div x-show="!conversations.length" class="px-6 py-16 text-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-outline" style="font-size:44px;">forum</span>
                        <p class="mt-2 text-sm">No conversations yet.</p>
                    </div>
                </div>
            </div>

            {{-- Chat (hidden on mobile until a conversation is explicitly opened) --}}
            <div class="col-span-12 md:col-span-8 xl:col-span-9 flex-col min-h-0 bg-surface-container-low/30 {{ $explicit ? 'flex' : 'hidden md:flex' }}">
                @if ($active)
                    <div x-data="supportInbox({
                            conversationId: {{ $active->id }},
                            messages: @js($messages),
                            pollUrl: @js(route('admin.support.messages', $active)),
                            historyUrl: @js(route('admin.support.history', $active)),
                            replyUrl: @js(route('admin.support.reply', $active)),
                            blockUrl: @js(route('admin.support.block', $active)),
                            blocked: {{ $active->isBlocked() ? 'true' : 'false' }},
                            online: {{ $active->isOnline() ? 'true' : 'false' }},
                            hasMore: {{ $hasMore ? 'true' : 'false' }},
                        })" class="flex flex-col min-h-0 h-full">

                        {{-- Header --}}
                        <div class="px-5 py-3.5 border-b border-outline-variant/60 bg-surface-container-lowest flex items-center gap-3">
                            <a href="{{ route('admin.support.index') }}" class="md:hidden -ml-1 p-1 text-on-surface-variant"><span class="material-symbols-outlined">arrow_back</span></a>
                            <div class="relative shrink-0">
                                <div class="w-10 h-10 rounded-full bg-primary/10 text-primary grid place-items-center font-bold">{{ strtoupper(mb_substr($active->name, 0, 1)) }}</div>
                                <span x-show="online" x-cloak class="absolute bottom-0 right-0 w-3 h-3 rounded-full bg-green-500 ring-2 ring-surface-container-lowest" title="Online"></span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-on-surface flex items-center gap-1.5">
                                    {{ $active->name }}
                                    @if ($active->isVerified())
                                        <span class="material-symbols-outlined text-primary text-[18px]" style="font-variation-settings:'FILL' 1;" title="Verified customer">verified</span>
                                    @endif
                                    <span x-show="blocked" x-cloak class="inline-flex items-center gap-0.5 text-error text-[11px] font-semibold bg-error/10 px-1.5 py-0.5 rounded">
                                        <span class="material-symbols-outlined text-[13px] leading-none">block</span> Blocked
                                    </span>
                                </p>
                                <p class="text-xs text-outline flex items-center gap-1">
                                    <span x-show="online" x-cloak class="text-green-600 font-medium">Online ·</span>
                                    <span>
                                        @if ($active->user_id)
                                            {{ $active->email ?? $active->user?->email }} · {{ $active->isVerified() ? 'Verified account' : 'Unverified account' }}
                                        @else
                                            Guest visitor
                                        @endif
                                    </span>
                                </p>
                            </div>
                            <div class="ml-auto shrink-0 flex items-center gap-1">
                                @can('support.reply')
                                    <button type="button" @click="toggleBlock()" :disabled="blockBusy"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition disabled:opacity-50"
                                        :class="blocked ? 'bg-error/10 text-error hover:bg-error/20' : 'text-on-surface-variant hover:bg-surface-container-high'"
                                        :title="blocked ? 'Allow this customer to message again' : 'Block this customer from sending messages'">
                                        <span class="material-symbols-outlined text-[18px]" x-text="blocked ? 'lock_open' : 'block'"></span>
                                        <span x-text="blocked ? 'Unblock' : 'Block'"></span>
                                    </button>
                                @endcan
                                @can('support.delete')
                                    <button type="button" title="Delete conversation"
                                        @click="$store.supportConfirm.ask(@js('Delete this conversation?'), @js('All messages with ' . $active->name . ' will be permanently removed. This cannot be undone.'), () => document.getElementById('delete-conversation-form').submit())"
                                        class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-error hover:bg-error/10 transition">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                @endcan
                            </div>
                        </div>

                        {{-- Messages --}}
                        <div x-ref="scroll" @scroll.passive="onScroll($event)" class="flex-1 overflow-y-auto p-5 space-y-3">
                            <div x-show="loadingMore" class="flex justify-center py-1">
                                <span class="material-symbols-outlined animate-spin text-outline text-[20px]">progress_activity</span>
                            </div>
                            <template x-for="m in messages" :key="m.id">
                                <div :class="m.from_admin ? 'justify-end' : 'justify-start'" class="flex">
                                    <div :class="m.from_admin ? 'bg-primary text-on-primary rounded-tr-none' : 'bg-surface-container-highest text-on-surface rounded-tl-none'"
                                        class="max-w-[75%] rounded-2xl px-4 py-2.5 text-sm shadow-sm">
                                        <p class="whitespace-pre-wrap break-words" x-text="m.body"></p>
                                        <p :class="m.from_admin ? 'text-on-primary/70' : 'text-outline'" class="text-[10px] mt-1 flex items-center justify-end gap-0.5">
                                            <span x-text="m.at"></span>
                                            <template x-if="m.from_admin">
                                                <span class="material-symbols-outlined text-[14px] leading-none"
                                                    :class="m.status === 'read' ? 'text-cyan-300' : 'text-on-primary/60'"
                                                    x-text="(m.status === 'sending' || m.status === 'sent') ? 'check' : 'done_all'"></span>
                                            </template>
                                        </p>
                                    </div>
                                </div>
                            </template>
                            <p x-show="!messages.length" class="text-center text-sm text-on-surface-variant py-10">No messages yet.</p>
                        </div>

                        {{-- Reply --}}
                        @can('support.reply')
                            <form @submit.prevent="send()" class="p-4 border-t border-outline-variant/60 bg-surface-container-lowest flex items-center gap-2">
                                {{-- Emoji picker (staff side only) --}}
                                <div class="relative shrink-0" @click.outside="emojiOpen = false">
                                    <button type="button" @click="emojiOpen = !emojiOpen" title="Emoji"
                                        class="w-11 h-11 grid place-items-center rounded-lg text-on-surface-variant hover:bg-surface-container-high transition"
                                        :class="emojiOpen && 'bg-surface-container-high text-primary'">
                                        <span class="material-symbols-outlined text-[22px] leading-none">mood</span>
                                    </button>
                                    <div x-show="emojiOpen" x-cloak x-transition.origin.bottom.left
                                        class="absolute bottom-full left-0 mb-2 w-64 max-h-52 overflow-y-auto p-2 grid grid-cols-7 gap-0.5 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-xl z-10">
                                        <template x-for="e in emojis" :key="e">
                                            <button type="button" @click="addEmoji(e)" x-text="e"
                                                class="w-8 h-8 grid place-items-center rounded-lg hover:bg-surface-container-high text-xl leading-none"></button>
                                        </template>
                                    </div>
                                </div>
                                <div class="relative flex-1">
                                    <textarea x-model="body" @keydown.enter.prevent="send()" rows="1" maxlength="1000" placeholder="Type a reply…"
                                        class="w-full resize-none bg-surface-container-low border border-outline-variant rounded-lg px-4 py-[11px] pr-16 text-sm leading-5 outline-none focus:ring-1 focus:ring-primary focus:border-primary h-11 max-h-32"></textarea>
                                    <span class="absolute bottom-2 right-2.5 text-[10px] tabular-nums pointer-events-none select-none"
                                        :class="body.length >= 1000 ? 'text-error' : (body.length >= 900 ? 'text-amber-500' : 'text-outline')"
                                        x-text="body.length + '/1000'"></span>
                                </div>
                                <button type="submit" :disabled="!body.trim() || sending"
                                    class="shrink-0 h-11 px-6 bg-primary text-on-primary font-semibold text-sm rounded-lg flex items-center gap-2 hover:brightness-110 active:scale-95 transition disabled:opacity-50">
                                    <span class="material-symbols-outlined text-[20px]">send</span> Send
                                </button>
                            </form>
                        @endcan
                    </div>
                    @can('support.delete')
                        <form id="delete-conversation-form" method="POST" action="{{ route('admin.support.destroy', $active) }}" class="hidden">
                            @csrf @method('DELETE')
                        </form>
                    @endcan
                @else
                    <div class="flex-1 grid place-items-center text-center text-on-surface-variant p-8">
                        <div>
                            <span class="material-symbols-outlined text-outline" style="font-size:56px;">chat</span>
                            <p class="mt-3 font-medium">Select a conversation</p>
                            <p class="text-sm">Pick a customer on the left to read and reply.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-admin.panel>

    {{-- Shared yes/no confirmation dialog (single, bulk and cleanup deletes). --}}
    <div x-data x-show="$store.supportConfirm.open" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4"
        @keydown.escape.window="$store.supportConfirm.close()" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/50" @click="$store.supportConfirm.close()" x-transition.opacity></div>
        <div class="relative w-full max-w-sm bg-surface-container-lowest dark:bg-surface-container border border-outline-variant rounded-2xl shadow-2xl p-6 text-center"
            x-show="$store.supportConfirm.open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="w-14 h-14 mx-auto rounded-full bg-error-container grid place-items-center mb-4">
                <span class="material-symbols-outlined text-error text-[28px]">delete_forever</span>
            </div>
            <h3 class="text-lg font-bold text-on-surface mb-1" x-text="$store.supportConfirm.title"></h3>
            <p class="text-sm text-on-surface-variant mb-6" x-text="$store.supportConfirm.message"></p>
            <div class="flex gap-3">
                <button type="button" @click="$store.supportConfirm.close()"
                    class="flex-1 py-2.5 border border-outline text-on-surface font-semibold text-sm rounded-lg hover:bg-surface-container transition-colors">No, keep</button>
                <button type="button" @click="$store.supportConfirm.yes()"
                    class="flex-1 py-2.5 bg-error text-white font-bold text-sm rounded-lg hover:brightness-110 active:scale-95 transition-all">Yes, delete</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Build + submit a real form so deletes flow through the normal
            // redirect + flash cycle (and the audit middleware).
            window.__postForm = (url, fields = {}, method = 'POST') => {
                const f = document.createElement('form');
                f.method = 'POST'; f.action = url; f.style.display = 'none';
                const add = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
                add('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                if (method !== 'POST') add('_method', method);
                Object.entries(fields).forEach(([k, v]) => Array.isArray(v) ? v.forEach(x => add(k + '[]', x)) : add(k, v));
                document.body.appendChild(f);
                f.submit();
            };

            document.addEventListener('alpine:init', () => {
                // One confirm dialog shared by every destructive action on this page.
                Alpine.store('supportConfirm', {
                    open: false, title: '', message: '', _fn: null,
                    ask(title, message, fn) { this.title = title; this.message = message; this._fn = fn; this.open = true; },
                    close() { this.open = false; this._fn = null; },
                    yes() { const f = this._fn; this.close(); if (f) f(); },
                });

                Alpine.data('supportInbox', (cfg) => ({
                    messages: cfg.messages || [],
                    body: '',
                    sending: false,
                    blocked: !!cfg.blocked,
                    blockBusy: false,
                    online: !!cfg.online,
                    emojiOpen: false,
                    emojis: ['👋','🙂','😊','😀','😁','😉','😎','🤝','👍','👏','🙏','💪','✅','⭐','🔥','🎉','🎁','💯','❤️','🙌','😅','🤔','😍','😢','💬','📦','🚚','🛒','💳','🧾','⏰','📞','📧','❌'],
                    addEmoji(e) { this.body += e; },
                    hasMore: !!cfg.hasMore,
                    loadingMore: false,
                    _poll: null,
                    _lastId: 0,
                    _minId: 0,
                    token() { return document.querySelector('meta[name="csrf-token"]')?.content; },
                    ping() { if (window.__supportBell) window.__supportBell(); },
                    init() {
                        this._lastId = this.messages.reduce((m, x) => Math.max(m, x.id), 0);
                        this._minId = this.messages.reduce((mn, x) => (x.id > 0 && (mn === 0 || x.id < mn)) ? x.id : mn, 0);
                        this.$nextTick(() => {
                            this.scrollDown();
                            // On mobile the chat pane is hidden behind the contact list; only claim to be
                            // the "open" thread (skips its bell/badge) and mark it read when it's on screen.
                            if (this.$root.offsetParent !== null) {
                                window.__activeSupportConversation = cfg.conversationId;
                                if (this.viewing()) this.refresh();
                            }
                        });
                        // Realtime: the global staff channel dispatches every message; append the ones for this thread.
                        this._onMsg = (ev) => { const m = ev.detail; if (m && m.conversation_id === cfg.conversationId) this.onEcho(m); };
                        this._onReceipt = (ev) => { const e = ev.detail; if (e && e.by === 'customer' && e.conversation_id === cfg.conversationId) this.onReceipt(e); };
                        window.addEventListener('support:message', this._onMsg);
                        window.addEventListener('support:receipt', this._onReceipt);
                        // "Read" requires focus — re-mark when staff returns to this tab.
                        this._onFocus = () => this.refresh();
                        window.addEventListener('focus', this._onFocus);
                        // Slow poll as a self-healing fallback if the socket drops.
                        this._poll = setInterval(() => this.refresh(), 15000);
                    },
                    destroy() {
                        if (this._poll) clearInterval(this._poll);
                        if (window.__activeSupportConversation === cfg.conversationId) window.__activeSupportConversation = null;
                        window.removeEventListener('support:message', this._onMsg);
                        window.removeEventListener('support:receipt', this._onReceipt);
                        window.removeEventListener('focus', this._onFocus);
                    },
                    // "Seen" requires the tab focused AND the chat pane actually on screen
                    // (on mobile it's hidden behind the contact list).
                    viewing() { return document.hasFocus() && this.$root.offsetParent !== null; },
                    onEcho(m) {
                        if (this.messages.some(x => x.id === m.id)) return;
                        this.messages.push(m);
                        this._lastId = Math.max(this._lastId, m.id);
                        this.$nextTick(() => this.scrollDown());
                        // A customer message landed in the open thread → ring, then mark it read.
                        if (!m.from_admin) { this.ping(); this.refresh(); }
                    },
                    // Customer delivered/read our replies → advance our own ticks (double, then blue).
                    onReceipt(e) {
                        this.messages.forEach(m => {
                            if (!m.from_admin) return;
                            if (e.type === 'read') m.status = 'read';
                            else if (e.type === 'delivered' && m.status !== 'read') m.status = 'delivered';
                        });
                    },
                    async refresh() {
                        try {
                            const r = await fetch(cfg.pollUrl + '?viewing=' + (this.viewing() ? 1 : 0), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                            if (!r.ok) return;
                            const d = await r.json();
                            if (typeof d.online === 'boolean') this.online = d.online;
                            const msgs = Array.isArray(d.messages) ? d.messages : [];
                            if (msgs.some(x => x.id > this._lastId && !x.from_admin)) this.ping();   // a new customer message arrived
                            const appended = this.applyServerRecent(msgs);
                            if (appended) this.$nextTick(() => this.scrollDown());
                        } catch (e) {}
                    },
                    // Merge the latest page from the server: update statuses in place, append new ones.
                    applyServerRecent(list) {
                        if (!Array.isArray(list)) return false;
                        const byId = new Map(this.messages.map(m => [m.id, m]));
                        let appended = false;
                        list.forEach(sm => {
                            const ex = byId.get(sm.id);
                            if (ex) { ex.status = sm.status; ex.at = sm.at; }
                            else if (sm.id > this._lastId) { this.messages.push(sm); byId.set(sm.id, sm); appended = true; }
                        });
                        list.forEach(sm => { if (sm.id > this._lastId) this._lastId = sm.id; });
                        if (!this._minId) this._minId = this.messages.reduce((mn, x) => (x.id > 0 && (mn === 0 || x.id < mn)) ? x.id : mn, 0);
                        return appended;
                    },
                    // Scrolled near the top → pull the previous page of older messages.
                    onScroll(e) {
                        if (e.target.scrollTop < 48 && this.hasMore && !this.loadingMore) this.loadMore();
                    },
                    async loadMore() {
                        if (this.loadingMore || !this.hasMore || this._minId <= 0) return;
                        this.loadingMore = true;
                        const el = this.$refs.scroll;
                        const prev = el ? el.scrollHeight : 0;
                        try {
                            const r = await fetch(cfg.historyUrl + '?before=' + this._minId, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                            if (r.ok) {
                                const d = await r.json();
                                const older = Array.isArray(d.messages) ? d.messages : [];
                                if (older.length) {
                                    this.messages = [...older, ...this.messages];
                                    this._minId = older[0].id;
                                    this.hasMore = !!d.has_more;
                                    // Keep the viewport anchored where the user was reading.
                                    this.$nextTick(() => { if (el) el.scrollTop = el.scrollHeight - prev; });
                                } else { this.hasMore = false; }
                            }
                        } catch (e) {}
                        this.loadingMore = false;
                    },
                    async send() {
                        const body = this.body.trim();
                        if (!body || this.sending) return;
                        this.sending = true;
                        const tempId = -Date.now();
                        this.messages.push({ id: tempId, body, from_admin: true, at: '', status: 'sending' });
                        this.body = '';
                        this.$nextTick(() => this.scrollDown());
                        let ok = false;
                        try {
                            const r = await fetch(cfg.replyUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.token() },
                                credentials: 'same-origin',
                                body: JSON.stringify({ body }),
                            });
                            ok = r.ok;
                            const d = await r.json().catch(() => ({}));
                            this.applyServerRecent(d.messages || []);
                        } catch (e) {}
                        if (ok) this.messages = this.messages.filter(m => m.id !== tempId);   // real reply merged in
                        this.sending = false;
                        this.$nextTick(() => this.scrollDown());
                    },
                    scrollDown() { const el = this.$refs.scroll; if (el) el.scrollTop = el.scrollHeight; },
                    async toggleBlock() {
                        if (this.blockBusy) return;
                        this.blockBusy = true;
                        try {
                            const r = await fetch(cfg.blockUrl, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': this.token(), Accept: 'application/json' },
                                credentials: 'same-origin',
                            });
                            const d = await r.json().catch(() => ({}));
                            if (typeof d.blocked === 'boolean') this.blocked = d.blocked;
                            // Nudge the contact list to refresh its blocked indicator.
                            window.dispatchEvent(new CustomEvent('support:receipt', { detail: {} }));
                        } catch (e) {}
                        this.blockBusy = false;
                    },
                }));

                // The contact list (left pane): re-fetches its feed whenever a realtime
                // message/receipt fires, so previews, order, timestamps and unread badges stay live.
                Alpine.data('supportList', (cfg) => ({
                    conversations: cfg.seed || [],
                    activeId: cfg.activeId,
                    search: cfg.search || '',
                    selectMode: false,
                    selected: [],
                    _poll: null,
                    _t: null,
                    toggleMode() { this.selectMode = ! this.selectMode; this.selected = []; },
                    toggle(id) { const i = this.selected.indexOf(id); i > -1 ? this.selected.splice(i, 1) : this.selected.push(id); },
                    isSel(id) { return this.selected.includes(id); },
                    bulkDelete() {
                        if (! this.selected.length) return;
                        Alpine.store('supportConfirm').ask(
                            'Delete ' + this.selected.length + ' conversation(s)?',
                            'All their messages will be permanently removed. This cannot be undone.',
                            () => window.__postForm(cfg.bulkUrl, { ids: this.selected }),
                        );
                    },
                    init() {
                        this._onEvt = () => this.schedule();
                        window.addEventListener('support:message', this._onEvt);
                        window.addEventListener('support:receipt', this._onEvt);
                        this._poll = setInterval(() => this.refresh(), 15000); // self-healing fallback
                    },
                    destroy() {
                        if (this._poll) clearInterval(this._poll);
                        if (this._t) clearTimeout(this._t);
                        window.removeEventListener('support:message', this._onEvt);
                        window.removeEventListener('support:receipt', this._onEvt);
                    },
                    // Coalesce a burst of events into one fetch.
                    schedule() { if (this._t) clearTimeout(this._t); this._t = setTimeout(() => this.refresh(), 300); },
                    async refresh() {
                        try {
                            const url = cfg.url + (this.search ? ('?search=' + encodeURIComponent(this.search)) : '');
                            const r = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                            if (!r.ok) return;
                            const d = await r.json();
                            const list = Array.isArray(d.conversations) ? d.conversations : [];
                            // The thread we're viewing is already read — keep its badge clear.
                            list.forEach(c => { if (c.id === this.activeId) c.unread = 0; });
                            this.conversations = list;
                            const badge = window.Alpine?.store('supportBadge');
                            if (badge && typeof d.total_unread === 'number') badge.count = d.total_unread;
                        } catch (e) {}
                    },
                    link(id) {
                        const p = new URLSearchParams();
                        if (this.search) p.set('search', this.search);
                        p.set('conversation', id);
                        return cfg.indexUrl + '?' + p.toString();
                    },
                }));
            });
        </script>
    @endpush
@endsection
