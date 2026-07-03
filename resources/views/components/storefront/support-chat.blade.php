{{-- Floating support chat widget. Polls for new messages; guests enter a name first. --}}
<div x-data="supportChat({
        stateUrl: @js(route('support.state')),
        startUrl: @js(route('support.start')),
        sendUrl: @js(route('support.send')),
        historyUrl: @js(route('support.history')),
        authed: @js(auth()->check()),
        authName: @js(auth()->user()?->name),
    })"
    :class="($store.compareBar && $store.compareBar.visible) ? 'bottom-28' : 'bottom-5'"
    class="fixed right-5 z-[60] flex flex-col items-end gap-3 print:hidden transition-all duration-200">

    {{-- Panel --}}
    <div x-show="open" x-cloak x-transition.origin.bottom.right
        class="w-[22rem] max-w-[calc(100vw-2.5rem)] h-[30rem] max-h-[calc(100vh-7rem)] bg-white rounded-2xl shadow-2xl border border-outline-variant/60 flex flex-col overflow-hidden">

        {{-- Header --}}
        <div class="bg-primary-container text-on-primary-container px-5 py-4 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-full bg-black/10 grid place-items-center"><span class="material-symbols-outlined">support_agent</span></span>
                <div>
                    <p class="font-semibold leading-tight">Support</p>
                    <p class="text-xs opacity-80">Typically replies in a few minutes</p>
                </div>
            </div>
            <button type="button" @click="open = false" class="w-8 h-8 grid place-items-center rounded-full hover:bg-black/10 transition"><span class="material-symbols-outlined text-[20px]">close</span></button>
        </div>

        {{-- Guest name step --}}
        <template x-if="!authed && !started">
            <form @submit.prevent="startChat()" class="flex-1 flex flex-col justify-center gap-4 p-6 text-center">
                <span class="material-symbols-outlined text-primary mx-auto" style="font-size:44px;">chat</span>
                <div>
                    <p class="font-semibold text-on-surface">Hi there 👋</p>
                    <p class="text-sm text-on-surface-variant mt-1">Enter your name to start chatting with us.</p>
                </div>
                <input type="text" x-model="guestName" maxlength="80" placeholder="Your name"
                    class="w-full border border-outline-variant rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-1 focus:ring-primary-container focus:border-primary-container">
                <button type="submit" :disabled="!guestName.trim()" class="bg-primary-container text-on-primary-container rounded-lg px-4 py-2.5 text-sm font-semibold disabled:opacity-50 hover:brightness-110 transition">Start chat</button>
            </form>
        </template>

        {{-- Conversation --}}
        <template x-if="authed || started">
            <div class="flex-1 flex flex-col min-h-0">
                <div x-ref="scroll" @scroll.passive="onScroll($event)" class="flex-1 overflow-y-auto p-4 space-y-3 bg-surface-container-low/40">
                    <div x-show="loadingMore" class="flex justify-center py-1">
                        <span class="material-symbols-outlined animate-spin text-outline text-[18px]">progress_activity</span>
                    </div>
                    <p x-show="!messages.length && !loadingMore" class="text-center text-sm text-on-surface-variant py-6">Send a message and we'll get right back to you.</p>
                    <template x-for="m in messages" :key="m.id">
                        <div :class="m.from_admin ? 'justify-start' : 'justify-end'" class="flex">
                            <div :class="m.from_admin ? 'bg-white text-on-surface rounded-tl-none' : 'bg-primary-container text-on-primary-container rounded-tr-none'"
                                class="max-w-[80%] rounded-2xl px-3.5 py-2 text-sm shadow-sm">
                                <p class="whitespace-pre-wrap break-words" x-text="m.body"></p>
                                <p :class="m.from_admin ? 'text-outline' : 'text-on-primary-container/70'" class="text-[10px] mt-0.5 flex items-center justify-end gap-0.5">
                                    <span x-text="m.at"></span>
                                    <template x-if="!m.from_admin">
                                        <span class="material-symbols-outlined text-[14px] leading-none"
                                            :class="m.status === 'read' ? 'text-blue-700' : 'text-on-primary-container/50'"
                                            x-text="(m.status === 'sending' || m.status === 'sent') ? 'check' : 'done_all'"></span>
                                    </template>
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="blocked" x-cloak class="p-4 border-t border-outline-variant/60 bg-error/5 text-center shrink-0">
                    <p class="text-xs text-error font-medium flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">block</span>
                        You can no longer send messages here.
                    </p>
                </div>
                <form x-show="!blocked" @submit.prevent="send()" class="p-3 border-t border-outline-variant/60 flex items-end gap-2 shrink-0 bg-white">
                    <div class="relative flex-1">
                        <textarea x-model="body" @keydown.enter.prevent="send()" rows="1" maxlength="1000" placeholder="Write a message…"
                            class="w-full resize-none border border-outline-variant rounded-lg px-3 py-2 pr-14 text-sm outline-none focus:ring-1 focus:ring-primary-container focus:border-primary-container max-h-24"></textarea>
                        <span class="absolute bottom-1.5 right-2 text-[10px] tabular-nums pointer-events-none select-none"
                            :class="body.length >= 1000 ? 'text-error' : (body.length >= 900 ? 'text-amber-500' : 'text-outline')"
                            x-text="body.length + '/1000'"></span>
                    </div>
                    <button type="submit" :disabled="!body.trim() || sending" class="w-10 h-10 grid place-items-center rounded-lg bg-primary-container text-on-primary-container disabled:opacity-50 hover:brightness-110 transition shrink-0"><span class="material-symbols-outlined text-[20px]">send</span></button>
                </form>
            </div>
        </template>
    </div>

    {{-- Toggle button --}}
    <button type="button" @click="toggle()"
        class="w-14 h-14 rounded-full bg-primary-container text-on-primary-container shadow-xl grid place-items-center hover:brightness-110 active:scale-95 transition relative">
        <span class="material-symbols-outlined" x-text="open ? 'close' : 'chat_bubble'"></span>
        <span x-show="!open && unread" x-cloak class="absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-error text-white text-[11px] font-bold grid place-items-center" x-text="unread"></span>
    </button>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('supportChat', (cfg) => ({
                    open: false,
                    authed: !!cfg.authed,
                    started: false,
                    guestName: cfg.authName || '',
                    messages: [],
                    body: '',
                    unread: 0,
                    sending: false,
                    blocked: false,
                    hasMore: false,
                    loadingMore: false,
                    _poll: null,
                    _lastId: 0,
                    _minId: 0,
                    _primed: false,
                    _channel: null,
                    _bell: null,
                    token() { return document.querySelector('meta[name="csrf-token"]')?.content; },
                    // One reusable audio, unlocked on the first user gesture so later programmatic
                    // plays (from sockets/timers) aren't blocked by the browser autoplay policy.
                    mountBell() {
                        this._bell = new Audio('/assets/bells/user_notif.mp3');
                        this._bell.volume = 0.5;
                        const unlock = () => {
                            const v = this._bell.volume; this._bell.volume = 0;
                            this._bell.play().then(() => { this._bell.pause(); this._bell.currentTime = 0; this._bell.volume = v; }).catch(() => { this._bell.volume = v; });
                            window.removeEventListener('pointerdown', unlock, true);
                            window.removeEventListener('keydown', unlock, true);
                        };
                        window.addEventListener('pointerdown', unlock, true);
                        window.addEventListener('keydown', unlock, true);
                    },
                    ping() { if (!this._bell) return; try { this._bell.currentTime = 0; this._bell.play().catch(() => {}); } catch (e) {} },
                    subscribe(token) {
                        if (!window.Echo || !token || this._channel === token) return;
                        this._channel = token;
                        window.Echo.channel('support.conversation.' + token)
                            .listen('.message.sent', (m) => this.onEcho(m))
                            .listen('.receipt', (e) => this.onReceipt(e))
                            .listen('.blocked', (e) => { if (e) this.blocked = !!e.blocked; });
                    },
                    // Staff delivered/read our messages → advance our own ticks (double, then blue).
                    onReceipt(e) {
                        if (!e || e.by !== 'admin') return;
                        this.messages.forEach(m => {
                            if (m.from_admin) return;
                            if (e.type === 'read') m.status = 'read';
                            else if (e.type === 'delivered' && m.status !== 'read') m.status = 'delivered';
                        });
                    },
                    onEcho(m) {
                        if (!m || this.messages.some(x => x.id === m.id)) return;
                        this.messages.push(m);
                        this._lastId = Math.max(this._lastId, m.id);
                        if (m.from_admin) {
                            if (!this.open) { this.ping(); this.unread++; }
                            // Tell the server we received it → staff sees the double (or blue) tick.
                            this.refresh();
                        }
                        if (this.open) this.$nextTick(() => this.scrollDown());
                    },
                    // A staff reply is only "read" while the panel is open AND this window is focused.
                    viewing() { return this.open && document.hasFocus(); },
                    init() {
                        this.mountBell();
                        // When the visitor returns to this tab with the chat open, mark replies read.
                        this._onFocus = () => { if (this.open) this.refresh(); };
                        window.addEventListener('focus', this._onFocus);
                        this.refresh().then(() => { if (this.started) this.startPolling(); });
                    },
                    toggle() {
                        this.open = !this.open;
                        if (this.open) { this.unread = 0; this.refresh(); this.startPolling(); this.$nextTick(() => this.scrollDown()); }
                    },
                    // Echo is realtime; this slow poll is a self-healing fallback if the socket drops.
                    startPolling() { if (this._poll) return; this._poll = setInterval(() => this.refresh(), 15000); },
                    async refresh() {
                        try {
                            const url = cfg.stateUrl + (cfg.stateUrl.includes('?') ? '&' : '?') + 'open=' + (this.viewing() ? 1 : 0);
                            const r = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                            if (!r.ok) return;
                            const d = await r.json();
                            if (d.token) this.subscribe(d.token);
                            const msgs = Array.isArray(d.messages) ? d.messages : [];
                            this.started = d.started;
                            this.authed = d.authenticated;
                            this.blocked = !!d.blocked;
                            if (this._primed) {
                                if (msgs.some(x => x.id > this._lastId && x.from_admin) && !this.open) this.ping();
                                this.applyServerRecent(msgs);
                            } else {
                                this.seed(msgs, d.has_more);
                                this._primed = true;
                            }
                            // Server is authoritative for the badge (unread staff replies); zero while open.
                            this.unread = this.open ? 0 : (d.unread || 0);
                            if (this.open) this.$nextTick(() => this.scrollDown());
                        } catch (e) {}
                    },
                    // Initial fill: newest page, oldest-first.
                    seed(list, hasMore) {
                        this.messages = Array.isArray(list) ? list.slice() : [];
                        this._lastId = this.messages.reduce((mx, m) => Math.max(mx, m.id), 0);
                        this._minId = this.messages.reduce((mn, m) => (m.id > 0 && (mn === 0 || m.id < mn)) ? m.id : mn, 0);
                        this.hasMore = !!hasMore;
                    },
                    // Merge the latest page: update statuses in place, append new messages.
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
                        if (!this._minId) this._minId = this.messages.reduce((mn, m) => (m.id > 0 && (mn === 0 || m.id < mn)) ? m.id : mn, 0);
                        return appended;
                    },
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
                                    this.$nextTick(() => { if (el) el.scrollTop = el.scrollHeight - prev; });
                                } else { this.hasMore = false; }
                            }
                        } catch (e) {}
                        this.loadingMore = false;
                    },
                    async startChat() {
                        if (!this.authed && !this.guestName.trim()) return;
                        await this.post(cfg.startUrl, { name: this.guestName });
                        this.started = true;
                        this.startPolling();
                        this.$nextTick(() => this.scrollDown());
                    },
                    async send() {
                        const body = this.body.trim();
                        if (!body || this.sending || this.blocked) return;
                        this.sending = true;
                        // Optimistic bubble with a single "sending" tick; the server response replaces it.
                        const tempId = -Date.now();
                        this.messages.push({ id: tempId, body, from_admin: false, at: '', status: 'sending' });
                        this.body = '';
                        this.$nextTick(() => this.scrollDown());
                        await this.post(cfg.sendUrl, { body, name: this.guestName });
                        this.messages = this.messages.filter(m => m.id !== tempId);   // real one merged on success; dropped on block/fail
                        this.started = true;
                        this.sending = false;
                        this.startPolling();
                        this.$nextTick(() => this.scrollDown());
                    },
                    async post(url, data) {
                        try {
                            const r = await fetch(url, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.token() },
                                credentials: 'same-origin',
                                body: JSON.stringify(data),
                            });
                            const d = await r.json().catch(() => ({}));
                            if (d.token) this.subscribe(d.token);
                            if (typeof d.blocked === 'boolean') this.blocked = d.blocked;
                            this.applyServerRecent(d.messages || []);
                            return r.ok;
                        } catch (e) { return false; }
                    },
                    scrollDown() { const el = this.$refs.scroll; if (el) el.scrollTop = el.scrollHeight; },
                }));
            });
        </script>
    @endpush
@endonce
