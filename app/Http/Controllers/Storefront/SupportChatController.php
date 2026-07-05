<?php

namespace App\Http\Controllers\Storefront;

use App\Events\SupportMessageSent;
use App\Events\SupportReceipt;
use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\SupportBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Customer-facing support chat (the floating widget). A logged-in customer gets
 * a conversation tied to their account; a guest gets one tied to their session.
 * Updates are polled (no realtime dependency).
 */
class SupportChatController extends Controller
{
    /** Messages loaded per page (initial view + each scroll-up fetch). */
    private const PER_PAGE = 20;

    /** Current state: whether a chat exists, the display name, and its messages. */
    public function state(Request $request): JsonResponse
    {
        $this->maybeNudgeAbandonedCheckout($request);

        $conv = $this->resolve(create: false);

        if ($conv) {
            $this->touchSeen($conv); // presence heartbeat → drives the "online" dot
            // The widget is loaded → staff replies have reached this device (double tick).
            $this->markDelivered($conv, staffMessages: true, by: 'customer');
            // Panel open → the customer is actually reading them (blue tick).
            if ($request->boolean('open')) {
                $this->markRead($conv, staffMessages: true, by: 'customer');
            }
        }

        $messages = $conv ? $this->recent($conv) : [];

        return response()->json([
            'started' => (bool) $conv,
            'authenticated' => Auth::check(),
            'name' => $conv?->name ?? Auth::user()?->name,
            'token' => $conv?->channelToken(),
            'blocked' => $conv?->isBlocked() ?? false,
            'unread' => $conv ? $conv->messages()->where('from_admin', true)->whereNull('read_at')->count() : 0,
            'messages' => $messages,
            'has_more' => $conv ? $this->hasOlder($conv, $messages[0]['id'] ?? null) : false,
        ]);
    }

    /** Older messages (before an id) for the widget's scroll-up lazy loading. */
    public function history(Request $request): JsonResponse
    {
        $conv = $this->resolve(create: false);
        $before = $request->integer('before');
        if (! $conv || ! $before) {
            return response()->json(['messages' => [], 'has_more' => false]);
        }

        $models = $conv->messages()->where('id', '<', $before)
            ->orderByDesc('id')->limit(self::PER_PAGE)->get()->sortBy('id')->values();

        return response()->json([
            'messages' => $models->map(fn (SupportMessage $m) => $this->row($m))->all(),
            'has_more' => $this->hasOlder($conv, $models->first()?->id),
        ]);
    }

    /** Begin a chat. Guests must supply a name; logged-in users reuse their account. */
    public function start(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            $request->validate(['name' => ['required', 'string', 'max:80']]);
        }

        $conv = $this->resolve(create: true, name: $request->string('name')->toString());
        $this->touchSeen($conv);

        return response()->json([
            'started' => true,
            'name' => $conv->name,
            'token' => $conv->channelToken(),
            'messages' => $this->recent($conv),
        ]);
    }

    /** Post a customer message (creates the conversation if needed). */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'name' => ['nullable', 'string', 'max:80'],
        ]);

        $conv = $this->resolve(create: true, name: $data['name'] ?? null);
        if (! $conv) {
            return response()->json(['message' => 'Please enter your name first.'], 422);
        }
        if ($conv->isBlocked()) {
            return response()->json(['blocked' => true, 'message' => 'You can no longer send messages in this chat.'], 403);
        }
        $this->touchSeen($conv);

        $message = $conv->messages()->create([
            'from_admin' => false,
            'user_id' => Auth::id(),
            'body' => $data['body'],
        ]);
        $conv->update(['last_message_at' => now(), 'status' => 'open']);
        $this->broadcastMessage($message, $conv);

        return response()->json(['token' => $conv->channelToken(), 'messages' => $this->recent($conv)]);
    }

    /** Best-effort realtime push (Reverb). Never fails the request if the socket server is down. */
    private function broadcastMessage(SupportMessage $message, SupportConversation $conv): void
    {
        try {
            broadcast(new SupportMessageSent($message, $conv->channelToken()));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    // ---- helpers -------------------------------------------------------------

    /**
     * Nudge a signed-in customer who reached checkout but wandered off without ordering.
     * Fires lazily off the widget's normal poll (no scheduler/queue needed): once they've
     * left the checkout page and a short grace period has passed, drop a one-time reminder.
     */
    private function maybeNudgeAbandonedCheckout(Request $request): void
    {
        if (! Auth::check() || session('co_nudged') || ! session('co_pending_at')) {
            return;
        }
        // Still on the checkout page, or too soon → wait.
        if (str_contains((string) $request->query('path'), 'checkout')) {
            return;
        }
        if (now()->timestamp - (int) session('co_pending_at') < 60) {
            return;
        }

        app(SupportBot::class)->notifyUser(Auth::user(),
            "🛍️ You're just one step away from placing your order!\n"
            . 'Pick up where you left off: ' . route('checkout'));

        session(['co_nudged' => true]);
        session()->forget('co_pending_at');
    }

    /** Presence heartbeat — records that the widget is currently live (drives the online dot). */
    private function touchSeen(SupportConversation $conv): void
    {
        $conv->updateQuietly(['last_seen_at' => now()]);
    }

    /** Find (or create) the visitor's conversation: by account if logged in, else by session. */
    private function resolve(bool $create, ?string $name = null): ?SupportConversation
    {
        if (Auth::check()) {
            $user = Auth::user();
            $conv = SupportConversation::where('user_id', $user->id)->latest('id')->first();
            if (! $conv && $create) {
                $conv = SupportConversation::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => 'open',
                ]);
            }

            return $conv;
        }

        $id = session('support_conversation_id');
        $conv = $id ? SupportConversation::whereNull('user_id')->find($id) : null;

        if (! $conv && $create && filled($name)) {
            $conv = SupportConversation::create([
                'name' => $name,
                'token' => Str::random(48),
                'status' => 'open',
            ]);
            session(['support_conversation_id' => $conv->id]);
        }

        return $conv;
    }

    /** Mark one side's messages delivered and pulse a "delivered" receipt to the other side. */
    private function markDelivered(SupportConversation $conv, bool $staffMessages, string $by): void
    {
        $changed = $conv->messages()->where('from_admin', $staffMessages)->whereNull('delivered_at')->update(['delivered_at' => now()]);
        if ($changed) {
            $this->broadcastReceipt($conv, $by, 'delivered');
        }
    }

    /** Mark one side's messages read and pulse a "read" receipt to the other side. */
    private function markRead(SupportConversation $conv, bool $staffMessages, string $by): void
    {
        $changed = $conv->messages()->where('from_admin', $staffMessages)->whereNull('read_at')->update(['read_at' => now()]);
        if ($changed) {
            $this->broadcastReceipt($conv, $by, 'read');
        }
    }

    /** Best-effort receipt push; never fails the request if the socket server is down. */
    private function broadcastReceipt(SupportConversation $conv, string $by, string $type): void
    {
        try {
            broadcast(new SupportReceipt($conv->channelToken(), $conv->id, $by, $type));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** The latest page of messages, oldest-first (initial view + polling). @return array<int, array<string, mixed>> */
    private function recent(SupportConversation $conv): array
    {
        return $conv->messages()->orderByDesc('id')->limit(self::PER_PAGE)->get()
            ->sortBy('id')->values()->map(fn (SupportMessage $m) => $this->row($m))->all();
    }

    /** Whether any messages exist older than $beforeId (drives the "load more" affordance). */
    private function hasOlder(SupportConversation $conv, ?int $beforeId): bool
    {
        return $beforeId ? $conv->messages()->where('id', '<', $beforeId)->exists() : false;
    }

    /** @return array<string, mixed> */
    private function row(SupportMessage $m): array
    {
        return [
            'id' => $m->id,
            'body' => $m->body,
            'from_admin' => $m->from_admin,
            'at' => $m->created_at?->format('H:i'),
            'status' => $m->read_at ? 'read' : ($m->delivered_at ? 'delivered' : 'sent'),
        ];
    }
}
