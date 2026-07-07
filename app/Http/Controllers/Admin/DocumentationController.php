<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * In-app documentation handbook (Admin → Help → Documentation).
 *
 * Content is a set of Blade partials under admin/docs/pages/*, listed and
 * ordered by config/documentation.php. This controller only resolves the
 * table of contents, the requested page and its prev/next neighbours.
 */
class DocumentationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:documentation.view')];
    }

    public function index(): View
    {
        return $this->show($this->firstSlug());
    }

    public function show(string $page): View
    {
        $flat = $this->pages();

        if (! isset($flat[$page])) {
            throw new NotFoundHttpException("Documentation page [{$page}] not found.");
        }

        $slugs = array_keys($flat);
        $pos = array_search($page, $slugs, true);

        return view('admin.docs.show', [
            'groups' => config('documentation.groups', []),
            'current' => $page,
            'meta' => $flat[$page],
            'prev' => $pos > 0 ? $this->link($flat, $slugs[$pos - 1]) : null,
            'next' => $pos < count($slugs) - 1 ? $this->link($flat, $slugs[$pos + 1]) : null,
        ]);
    }

    /**
     * Flatten the manifest to slug => meta (meta gains its slug + group label).
     *
     * @return array<string, array<string, mixed>>
     */
    private function pages(): array
    {
        $flat = [];

        foreach (config('documentation.groups', []) as $group) {
            foreach ($group['pages'] ?? [] as $slug => $meta) {
                $flat[$slug] = $meta + ['slug' => $slug, 'group' => $group['label']];
            }
        }

        return $flat;
    }

    private function firstSlug(): string
    {
        return array_key_first($this->pages()) ?? 'overview';
    }

    /**
     * @param  array<string, array<string, mixed>>  $flat
     * @return array{slug: string, title: string}
     */
    private function link(array $flat, string $slug): array
    {
        return ['slug' => $slug, 'title' => $flat[$slug]['title']];
    }
}
