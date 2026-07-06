<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromoCardRequest;
use App\Models\Media;
use App\Models\PromoCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PromoCardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:promo-cards.view', only: ['index']),
            new Middleware('can:promo-cards.create', only: ['create', 'store']),
            new Middleware('can:promo-cards.edit', only: ['edit', 'update']),
            new Middleware('can:promo-cards.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $cards = PromoCard::query()->with('image:id,disk,path')->ordered()->get();

        return view('admin.promo-cards.index', [
            'cards' => $cards,
            'stats' => [
                'total' => $cards->count(),
                'active' => $cards->where('is_active', true)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.promo-cards.create', [
            'card' => new PromoCard(['is_active' => true, 'sort_order' => 0, 'display_type' => PromoCard::TYPE_SHOP]),
            'mediaItems' => $this->mediaItems(),
        ]);
    }

    public function store(PromoCardRequest $request): RedirectResponse
    {
        PromoCard::create($request->validated());

        return redirect()
            ->route('admin.promo-cards.index')
            ->with('status', 'Promo card created.');
    }

    public function edit(PromoCard $promoCard): View
    {
        return view('admin.promo-cards.edit', [
            'card' => $promoCard,
            'mediaItems' => $this->mediaItems(),
        ]);
    }

    public function update(PromoCardRequest $request, PromoCard $promoCard): RedirectResponse
    {
        $promoCard->update($request->validated());

        return redirect()
            ->route('admin.promo-cards.index')
            ->with('status', 'Promo card updated.');
    }

    public function destroy(PromoCard $promoCard): RedirectResponse
    {
        $promoCard->delete();

        return redirect()
            ->route('admin.promo-cards.index')
            ->with('status', 'Promo card deleted.');
    }

    /**
     * Existing media for the visual image picker.
     *
     * @return \Illuminate\Support\Collection<int, array{id:int, url:string, title:string}>
     */
    private function mediaItems(): \Illuminate\Support\Collection
    {
        return Media::query()
            ->latest('id')
            ->limit(200)
            ->get(['id', 'disk', 'path', 'title'])
            ->map(fn (Media $m) => [
                'id' => $m->id,
                'url' => $m->url,
                'title' => $m->title ?: basename($m->path),
            ]);
    }
}
