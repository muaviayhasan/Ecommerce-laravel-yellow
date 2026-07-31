<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HeroSlideRequest;
use App\Models\HeroSlide;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class HeroSlideController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:hero-slides.view', only: ['index']),
            new Middleware('can:hero-slides.create', only: ['create', 'store', 'duplicate']),
            new Middleware('can:hero-slides.edit', only: ['edit', 'update']),
            new Middleware('can:hero-slides.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $slides = HeroSlide::query()->with('image:id,disk,path');

        $this->applyTableSort($slides, $request, [
            'headline' => 'line1',
            'sort' => 'sort_order',
            'status' => 'is_active',
        ], fn ($q) => $q->orderBy('sort_order')->orderBy('id'));

        $perPage = $this->perPageFor($request);
        $slides = $slides->paginate($perPage)->withQueryString();

        return view('admin.hero-slides.index', [
            'slides' => $slides,
            'perPage' => $perPage,
            'stats' => [
                'total' => HeroSlide::count(),
                'active' => HeroSlide::where('is_active', true)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.hero-slides.create', [
            'slide' => new HeroSlide(['is_active' => true, 'sort_order' => 0]),
            'mediaItems' => $this->mediaItems(),
        ]);
    }

    public function store(HeroSlideRequest $request): RedirectResponse
    {
        HeroSlide::create($request->validated());

        return redirect()
            ->route('admin.hero-slides.index')
            ->with('status', 'Hero slide created.');
    }

    /**
     * Copy a slide so a near-identical one (same layout, different image or offer)
     * doesn't have to be retyped. Mirrors products.duplicate: the copy is created
     * switched OFF, so duplicating can never quietly add a slide to the live
     * carousel, and you land on its edit form to make the changes you copied it for.
     */
    public function duplicate(HeroSlide $heroSlide): RedirectResponse
    {
        $copy = $heroSlide->replicate();
        $copy->line1 = trim($heroSlide->line1 . ' (Copy)');
        $copy->is_active = false;
        // Park it at the end rather than beside the original — renumbering the
        // others would reorder a carousel that is live right now.
        $copy->sort_order = (int) HeroSlide::max('sort_order') + 1;
        $copy->save();

        return redirect()
            ->route('admin.hero-slides.edit', $copy)
            ->with('status', 'Slide duplicated. The copy is inactive and last in order — edit it, then switch it on.');
    }

    public function edit(HeroSlide $heroSlide): View
    {
        return view('admin.hero-slides.edit', [
            'slide' => $heroSlide,
            'mediaItems' => $this->mediaItems(),
        ]);
    }

    public function update(HeroSlideRequest $request, HeroSlide $heroSlide): RedirectResponse
    {
        $heroSlide->update($request->validated());

        return redirect()
            ->route('admin.hero-slides.index')
            ->with('status', 'Hero slide updated.');
    }

    public function destroy(HeroSlide $heroSlide): RedirectResponse
    {
        $heroSlide->delete();

        return redirect()
            ->route('admin.hero-slides.index')
            ->with('status', 'Hero slide deleted.');
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
