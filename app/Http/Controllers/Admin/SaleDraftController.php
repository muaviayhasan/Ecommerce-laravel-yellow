<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleDraft;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Parked sales for the POS and vendor-sale screens. Guarded per channel: POS
 * drafts need pos.sell, vendor drafts need orders.create — the same permission
 * that guards completing the sale itself.
 */
class SaleDraftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $channel = (string) $request->query('channel');
        $this->authorizeChannel($channel);

        return response()->json(
            SaleDraft::query()
                ->where('channel', $channel)
                ->with('creator:id,name')
                ->latest('id')->take(30)->get()
                ->map(fn (SaleDraft $d) => $this->row($d))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', Rule::in(['pos', 'vendor'])],
            'label' => ['nullable', 'string', 'max:120'],
            'payload' => ['required', 'array'],
            'payload.cart' => ['required', 'array', 'min:1'],
        ]);

        $this->authorizeChannel($data['channel']);

        // validated() would strip payload keys that carry no nested rules, so
        // persist the full (validated-shape) payload from the raw input.
        $payload = (array) $request->input('payload', []);
        // The column is only a convenience link — ignore ids that don't resolve.
        $customerId = $payload['customerId'] ?? null;
        $customerId = is_numeric($customerId) && \App\Models\Customer::whereKey((int) $customerId)->exists()
            ? (int) $customerId
            : null;

        $draft = SaleDraft::create([
            'channel' => $data['channel'],
            'label' => ($data['label'] ?? null) ?: 'Draft',
            'customer_id' => is_numeric($customerId) ? (int) $customerId : null,
            'payload' => $payload,
            'created_by' => auth()->id(),
        ]);

        return response()->json($this->row($draft->fresh('creator')));
    }

    public function destroy(SaleDraft $saleDraft): JsonResponse
    {
        $this->authorizeChannel($saleDraft->channel);

        $saleDraft->delete();

        return response()->json(['ok' => true]);
    }

    /** @return array<string, mixed> */
    private function row(SaleDraft $d): array
    {
        return [
            'id' => $d->id,
            'label' => $d->label,
            'items' => count($d->payload['cart'] ?? []),
            'time' => $d->created_at?->diffForHumans(null, true) . ' ago',
            'by' => $d->creator?->name,
            'payload' => $d->payload,
        ];
    }

    private function authorizeChannel(string $channel): void
    {
        abort_unless(in_array($channel, ['pos', 'vendor'], true), 422);
        abort_unless(
            auth()->user()?->can($channel === 'pos' ? 'pos.sell' : 'orders.create'),
            403
        );
    }
}
