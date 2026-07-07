<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lightweight JSON feed of gallery media for the image pickers. Paginates newest
 * first so the picker can lazy-load a page at a time as the admin scrolls.
 * Gated by `auth` (applied on the admin route group) — any staff member who can
 * open a form with a picker can browse the shared media library.
 */
class MediaController extends Controller
{
    public function browse(Request $request): JsonResponse
    {
        $perPage = 10;

        $media = Media::query()
            ->latest('id')
            ->paginate($perPage, ['id', 'disk', 'path', 'title']);

        return response()->json([
            'data' => $media->getCollection()->map(fn (Media $m) => [
                'id' => $m->id,
                'url' => $m->url,
                'title' => $m->title ?: basename($m->path),
            ])->all(),
            'next' => $media->hasMorePages() ? $media->currentPage() + 1 : null,
        ]);
    }
}
