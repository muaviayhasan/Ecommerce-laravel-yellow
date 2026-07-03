{{-- Floating support chat widget. Polls for new messages; guests enter a name first. --}}
<div x-data="supportChat({
        stateUrl: @js(route('support.state')),
        startUrl: @js(route('support.start')),
        sendUrl: @js(route('support.send')),
        authed: @js(auth()->check()),
        authName: @js(auth()->user()?->name),
    })" class="fixed bottom-5 right-5 z-[60] flex flex-col items-end gap-3 print:hidden">

    {{-- Panel --}}
    <div x-show="open" x-cloak x-transition.origin.bottom.right
        class="w-[22rem] max-w-[calc(100vw-2.5rem)] h-[30rem] max-h-[calc(100vh-7rem)] bg-white rounded-2xl shadow-2xl border border-outline-variant/60 flex flex-col overflow-hidden">

        {{-- Header --}}
        <div class="bg-[#2563eb] text-white px-5 py-4 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-full bg-white/20 grid place-items-center"><span class="material-symbols-outlined">support_agent</span></span>
                <div>
                    <p class="font-semibold leading-tight">Support</p>
                    <p class="text-xs opacity-80">Typically replies in a few minutes</p>
                </div>
            </div>
            <button type="button" @click="open = false" class="w-8 h-8 grid place-items-center rounded-full hover:bg-white/20 transition"><span class="material-symbols-outlined text-[20px]">close</span></button>
        </div>

        {{-- Guest name step --}}
        <template x-if="!authed && !started">
            <form @submit.prevent="startChat()" class="flex-1 flex flex-col justify-center gap-4 p-6 text-center">
                <span class="material-symbols-outlined text-[#2563eb] mx-auto" style="font-size:44px;">chat</span>
                <div>
                    <p class="font-semibold text-on-surface">Hi there 👋</p>
                    <p class="text-sm text-on-surface-variant mt-1">Enter your name to start chatting with us.</p>
                </div>
                <input type="text" x-model="guestName" maxlength="80" placeholder="Your name"
                    class="w-full border border-outline-variant rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-1 focus:ring-[#2563eb] focus:border-[#2563eb]">
                <button type="submit" :disabled="!guestName.trim()" class="bg-[#2563eb] text-white rounded-lg px-4 py-2.5 text-sm font-semibold disabled:opacity-50 hover:brightness-110 transition">Start chat</button>
            </form>
        </template>

        {{-- Conversation --}}
        <template x-if="authed || started">
            <div class="flex-1 flex flex-col min-h-0">
                <div x-ref="scroll" class="flex-1 overflow-y-auto p-4 space-y-3 bg-surface-container-low/40">
                    <p x-show="!messages.length" class="text-center text-sm text-on-surface-variant py-6">Send a message and we'll get right back to you.</p>
                    <template x-for="m in messages" :key="m.id">
                        <div :class="m.from_admin ? 'justify-start' : 'justify-end'" class="flex">
                            <div :class="m.from_admin ? 'bg-white text-on-surface rounded-tl-none' : 'bg-[#2563eb] text-white rounded-tr-none'"
                                class="max-w-[80%] rounded-2xl px-3.5 py-2 text-sm shadow-sm">
                                <p class="whitespace-pre-wrap break-words" x-text="m.body"></p>
                                <p :class="m.from_admin ? 'text-outline' : 'text-white/70'" class="text-[10px] mt-0.5 flex items-center justify-end gap-0.5">
                                    <span x-text="m.at"></span>
                                    <template x-if="!m.from_admin">
                                        <span class="material-symbols-outlined text-[14px] leading-none"
                                            :class="m.status === 'read' ? 'text-cyan-300' : 'text-white/60'"
                                            x-text="(m.status === 'sending' || m.status === 'sent') ? 'check' : 'done_all'"></span>
                                    </template>
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
                <form @submit.prevent="send()" class="p-3 border-t border-outline-variant/60 flex items-end gap-2 shrink-0 bg-white">
                    <div class="relative flex-1">
                        <textarea x-model="body" @keydown.enter.prevent="send()" rows="1" maxlength="1000" placeholder="Write a message…"
                            class="w-full resize-none border border-outline-variant rounded-lg px-3 py-2 pr-14 text-sm outline-none focus:ring-1 focus:ring-[#2563eb] focus:border-[#2563eb] max-h-24"></textarea>
                        <span class="absolute bottom-1.5 right-2 text-[10px] tabular-nums pointer-events-none select-none"
                            :class="body.length >= 1000 ? 'text-error' : (body.length >= 900 ? 'text-amber-500' : 'text-outline')"
                            x-text="body.length + '/1000'"></span>
                    </div>
                    <button type="submit" :disabled="!body.trim() || sending" class="w-10 h-10 grid place-items-center rounded-lg bg-[#2563eb] text-white disabled:opacity-50 hover:brightness-110 transition shrink-0"><span class="material-symbols-outlined text-[20px]">send</span></button>
                </form>
            </div>
        </template>
    </div>

    {{-- Toggle button --}}
    <button type="button" @click="toggle()"
        class="w-14 h-14 rounded-full bg-[#2563eb] text-white shadow-xl grid place-items-center hover:brightness-110 active:scale-95 transition relative">
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
                    _poll: null,
                    _lastId: 0,
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
                            .listen('.receipt', (e) => this.onReceipt(e));
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
                            const maxId = msgs.reduce((m, x) => Math.max(m, x.id), 0);
                            if (this._primed) {
                                const fresh = msgs.filter(x => x.id > this._lastId && x.from_admin);
                                if (fresh.length && !this.open) this.ping();
                            }
                            this._lastId = Math.max(this._lastId, maxId);
                            this._primed = true;
                            this.messages = msgs;
                            // Server is authoritative for the badge (unread staff replies); zero while open.
                            this.unread = this.open ? 0 : (d.unread || 0);
                            if (this.open) this.$nextTick(() => this.scrollDown());
                        } catch (e) {}
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
                        if (!body || this.sending) return;
                        this.sending = true;
                        // Optimistic bubble with a single "sending" tick; the server response replaces it.
                        this.messages.push({ id: -Date.now(), body, from_admin: false, at: '', status: 'sending' });
                        this.body = '';
                        this.$nextTick(() => this.scrollDown());
                        await this.post(cfg.sendUrl, { body, name: this.guestName });
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
                            if (Array.isArray(d.messages)) { this.messages = d.messages; this._lastId = d.messages.reduce((m, x) => Math.max(m, x.id), this._lastId); }
                        } catch (e) {}
                    },
                    scrollDown() { const el = this.$refs.scroll; if (el) el.scrollTop = el.scrollHeight; },
                }));
            });
        </script>
    @endpush
@endonce
