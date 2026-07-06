<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InfoBarItemRequest;
use App\Models\InfoBarItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class InfoBarItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:info-bar-items.view', only: ['index']),
            new Middleware('can:info-bar-items.create', only: ['create', 'store']),
            new Middleware('can:info-bar-items.edit', only: ['edit', 'update']),
            new Middleware('can:info-bar-items.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $items = InfoBarItem::query()->ordered()->get();

        return view('admin.info-bar-items.index', [
            'items' => $items,
            'stats' => [
                'total' => $items->count(),
                'active' => $items->where('is_active', true)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.info-bar-items.create', [
            'item' => new InfoBarItem(['is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(InfoBarItemRequest $request): RedirectResponse
    {
        InfoBarItem::create($request->validated());

        return redirect()
            ->route('admin.info-bar-items.index')
            ->with('status', 'Info bar item created.');
    }

    public function edit(InfoBarItem $infoBarItem): View
    {
        return view('admin.info-bar-items.edit', [
            'item' => $infoBarItem,
        ]);
    }

    public function update(InfoBarItemRequest $request, InfoBarItem $infoBarItem): RedirectResponse
    {
        $infoBarItem->update($request->validated());

        return redirect()
            ->route('admin.info-bar-items.index')
            ->with('status', 'Info bar item updated.');
    }

    public function destroy(InfoBarItem $infoBarItem): RedirectResponse
    {
        $infoBarItem->delete();

        return redirect()
            ->route('admin.info-bar-items.index')
            ->with('status', 'Info bar item deleted.');
    }
}
