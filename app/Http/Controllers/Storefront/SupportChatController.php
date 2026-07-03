<?php

namespace App\Http\Controllers\Storefront;

use App\Events\SupportMessageSent;
use App\Events\SupportReceipt;
use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
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
    /** Current state: whether a chat exists, the display name, and its messages. */
    public function state(Request $request): JsonResponse
    {
        $conv = $this->resolve(create: false);

        if ($conv) {
            // The widget is loaded → staff replies have reached this device (double tick).
            $this->markDelivered($conv, staffMessages: true, by: 'customer');
            // Panel open → the customer is actually reading them (blue tick).
            if ($request->boolean('open')) {
                $this->markRead($conv, staffMessages: true, by: 'customer');
            }
        }

        return response()->json([
            'started' => (bool) $conv,
            'authenticated' => Auth::check(),
            'name' => $conv?->name ?? Auth::user()?->name,
            'token' => $conv?->channelToken(),
            'blocked' => $conv?->isBlocked() ?? false,
            'unread' => $conv ? $conv->messages()->where('from_admin', true)->whereNull('read_at')->count() : 0,
            'messages' => $conv ? $this->format($conv) : [],
        ]);
    }

    /** Begin a chat. Guests must supply a name; logged-in users reuse their account. */
    public function start(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            $request->validate(['name' => ['required', 'string', 'max:80']]);
        }

        $conv = $this->resolve(create: true, name: $request->string('name')->toString());

        return response()->json([
            'started' => true,
            'name' => $conv->name,
            'token' => $conv->channelToken(),
            'messages' => $this->format($conv),
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

        $message = $conv->messages()->create([
            'from_admin' => false,
            'user_id' => Auth::id(),
            'body' => $data['body'],
        ]);
        $conv->update(['last_message_at' => now(), 'status' => 'open']);
        $this->broadcastMessage($message, $conv);

        return response()->json(['token' => $conv->channelToken(), 'messages' => $this->format($conv)]);
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

    /** @return array<int, array<string, mixed>> */
    private function format(SupportConversation $conv): array
    {
        return $conv->messages()->orderBy('id')->get()->map(fn (SupportMessage $m) => [
            'id' => $m->id,
            'body' => $m->body,
            'from_admin' => $m->from_admin,
            'at' => $m->created_at?->format('H:i'),
            'status' => $m->read_at ? 'read' : ($m->delivered_at ? 'delivered' : 'sent'),
        ])->all();
    }
}
