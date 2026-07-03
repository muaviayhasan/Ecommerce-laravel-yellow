<?php

namespace App\Http\Controllers\Admin;

use App\Events\SupportMessageSent;
use App\Events\SupportReceipt;
use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** Staff support inbox — the admin side of the customer chat widget. */
class SupportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:support.view', only: ['index', 'messages', 'delivered', 'conversations']),
            new Middleware('can:support.reply', only: ['reply']),
        ];
    }

    public function index(Request $request): View
    {
        $conversations = $this->conversationQuery($request)->get();

        $active = $request->integer('conversation')
            ? $conversations->firstWhere('id', $request->integer('conversation'))
                ?? SupportConversation::with('user')->find($request->integer('conversation'))
            : $conversations->first();

        if ($active) {
            $active->loadMissing('user');
        }

        // Opening the inbox delivers every visible customer message to staff (double tick);
        // the thread staff is actually looking at is marked fully read (blue tick) instead.
        $this->markDeliveredForList($conversations, exceptId: $active?->id);
        if ($active) {
            $this->markRead($active);
        }

        $activeId = $active?->id;

        return view('admin.support.index', [
            'conversationsData' => $conversations->map(function (SupportConversation $c) use ($activeId) {
                $row = $this->summarize($c);
                if ($c->id === $activeId) {
                    $row['unread'] = 0; // just marked read on open
                }

                return $row;
            })->values(),
            'active' => $active,
            'messages' => $active ? $this->format($active) : collect(),
            'filters' => $request->only('search'),
        ]);
    }

    /** JSON feed for the inbox contact list — drives its realtime updates. */
    public function conversations(Request $request): JsonResponse
    {
        return response()->json([
            'conversations' => $this->conversationQuery($request)->get()
                ->map(fn (SupportConversation $c) => $this->summarize($c))->values(),
            'total_unread' => SupportMessage::where('from_admin', false)->whereNull('read_at')->count(),
        ]);
    }

    /** Poll endpoint — messages for one conversation; marks customer messages read only while
     *  the staff tab is actually focused (so "seen" doesn't fire for a backgrounded window). */
    public function messages(SupportConversation $conversation, Request $request): JsonResponse
    {
        if ($request->boolean('viewing', true)) {
            $this->markRead($conversation);
        }

        return response()->json(['messages' => $this->format($conversation)]);
    }

    /** Realtime delivery ack — the staff browser received a customer message (double tick). */
    public function delivered(SupportConversation $conversation): JsonResponse
    {
        $this->markDelivered($conversation);

        return response()->json(['ok' => true]);
    }

    public function reply(Request $request, SupportConversation $conversation): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);

        $message = $conversation->messages()->create([
            'from_admin' => true,
            'user_id' => auth()->id(),
            'body' => $data['body'],
        ]);
        $conversation->update(['last_message_at' => now()]);

        try {
            broadcast(new SupportMessageSent($message, $conversation->channelToken()));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['messages' => $this->format($conversation)]);
    }

    /** Base query for the inbox contact list (honours the search filter). */
    private function conversationQuery(Request $request)
    {
        return SupportConversation::query()
            ->with(['user:id,email,email_verified_at', 'lastMessage'])
            ->withCount(['messages as unread' => fn ($q) => $q->where('from_admin', false)->whereNull('read_at')])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->string('search') . '%'))
            ->orderByDesc('last_message_at')->orderByDesc('id')
            ->limit(200);
    }

    /** One contact row for the inbox list. @return array<string, mixed> */
    private function summarize(SupportConversation $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'initial' => strtoupper(mb_substr($c->name ?? '?', 0, 1)),
            'verified' => $c->isVerified(),
            'preview' => $c->lastMessage?->body ?? 'No messages yet',
            'from_admin' => (bool) $c->lastMessage?->from_admin,
            'time' => $c->last_message_at?->diffForHumans(null, true),
            'unread' => (int) $c->unread,
        ];
    }

    /** Bulk-mark undelivered customer messages across the inbox list as delivered. */
    private function markDeliveredForList(Collection $conversations, ?int $exceptId): void
    {
        $ids = $conversations->pluck('id')->reject(fn ($id) => $id === $exceptId)->values();
        if ($ids->isEmpty()) {
            return;
        }

        $affected = SupportMessage::whereIn('support_conversation_id', $ids)
            ->where('from_admin', false)->whereNull('delivered_at')
            ->pluck('support_conversation_id')->unique();
        if ($affected->isEmpty()) {
            return;
        }

        SupportMessage::whereIn('support_conversation_id', $affected)
            ->where('from_admin', false)->whereNull('delivered_at')
            ->update(['delivered_at' => now()]);

        foreach ($conversations->whereIn('id', $affected) as $conv) {
            $this->broadcastReceipt($conv, 'admin', 'delivered');
        }
    }

    /** Mark the customer's messages delivered and pulse a "delivered" receipt to the widget. */
    private function markDelivered(SupportConversation $conversation): void
    {
        $changed = $conversation->messages()->where('from_admin', false)->whereNull('delivered_at')->update(['delivered_at' => now()]);
        if ($changed) {
            $this->broadcastReceipt($conversation, 'admin', 'delivered');
        }
    }

    /** Mark the customer's messages read and pulse a "read" receipt to the widget. */
    private function markRead(SupportConversation $conversation): void
    {
        $changed = $conversation->messages()->where('from_admin', false)->whereNull('read_at')->update(['read_at' => now()]);
        if ($changed) {
            $this->broadcastReceipt($conversation, 'admin', 'read');
        }
    }

    /** Best-effort receipt push; never fails the request if the socket server is down. */
    private function broadcastReceipt(SupportConversation $conversation, string $by, string $type): void
    {
        try {
            broadcast(new SupportReceipt($conversation->channelToken(), $conversation->id, $by, $type));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function format(SupportConversation $conversation)
    {
        return $conversation->messages()->orderBy('id')->get()->map(fn (SupportMessage $m) => [
            'id' => $m->id,
            'body' => $m->body,
            'from_admin' => $m->from_admin,
            'at' => $m->created_at?->format('d M, H:i'),
            'status' => $m->read_at ? 'read' : ($m->delivered_at ? 'delivered' : 'sent'),
        ]);
    }
}
