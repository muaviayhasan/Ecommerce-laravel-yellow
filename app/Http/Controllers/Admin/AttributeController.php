<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttributeRequest;
use App\Models\Attribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttributeController extends Controller implements HasMiddleware
{
    private const TYPES = ['select' => 'Dropdown', 'swatch' => 'Colour swatch', 'radio' => 'Radio buttons'];

    public static function middleware(): array
    {
        return [
            new Middleware('can:attributes.view', only: ['index']),
            new Middleware('can:attributes.create', only: ['create', 'store']),
            new Middleware('can:attributes.edit', only: ['edit', 'update']),
            new Middleware('can:attributes.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $attributes = Attribute::query()
            ->with(['values' => fn ($q) => $q->orderBy('sort_order')])
            ->withCount('values')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('code', 'like', $term));
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('variation'), fn ($q) => $q->where('is_variation', $request->string('variation') === 'yes'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(per_page())
            ->withQueryString();

        return view('admin.attributes.index', [
            'attributes' => $attributes,
            'types' => self::TYPES,
            'stats' => [
                'total' => Attribute::count(),
                'variation' => Attribute::where('is_variation', true)->count(),
                'values' => \App\Models\AttributeValue::count(),
            ],
            'filters' => $request->only('search', 'type', 'variation'),
        ]);
    }

    public function create(): View
    {
        return view('admin.attributes.create', [
            'attribute' => new Attribute(['type' => 'select', 'is_variation' => true, 'sort_order' => 0]),
            'types' => self::TYPES,
        ]);
    }

    public function store(AttributeRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $attribute = Attribute::create($request->safe()->except('values'));
            $this->syncValues($attribute, $request->input('values', []));
        });

        return redirect()->route('admin.attributes.index')->with('status', 'Attribute created.');
    }

    public function edit(Attribute $attribute): View
    {
        $attribute->load(['values' => fn ($q) => $q->orderBy('sort_order')]);

        return view('admin.attributes.edit', [
            'attribute' => $attribute,
            'types' => self::TYPES,
        ]);
    }

    public function update(AttributeRequest $request, Attribute $attribute): RedirectResponse
    {
        DB::transaction(function () use ($request, $attribute) {
            $attribute->update($request->safe()->except('values'));
            $this->syncValues($attribute, $request->input('values', []));
        });

        return redirect()->route('admin.attributes.index')->with('status', 'Attribute updated.');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        // attribute_values cascade-delete with the attribute; detach any variant links first.
        foreach ($attribute->values as $value) {
            $value->variants()->detach();
        }

        $attribute->delete();

        return redirect()->route('admin.attributes.index')->with('status', 'Attribute deleted.');
    }

    /**
     * Upsert the submitted value rows and remove any that were deleted in the form.
     * Blank-label rows are ignored; `value` defaults to a slug of the label.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncValues(Attribute $attribute, array $rows): void
    {
        $isSwatch = $attribute->type === 'swatch';
        $keepIds = [];

        foreach (array_values($rows) as $index => $row) {
            $label = trim((string) ($row['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $payload = [
                'label' => $label,
                'value' => filled($row['value'] ?? null) ? Str::slug((string) $row['value']) : Str::slug($label),
                'color_hex' => $isSwatch ? ($row['color_hex'] ?? null) : null,
                'sort_order' => (int) ($row['sort_order'] ?? $index),
            ];

            $existing = ! empty($row['id'])
                ? $attribute->values()->whereKey($row['id'])->first()
                : null;

            if ($existing) {
                $existing->update($payload);
                $keepIds[] = $existing->id;
            } else {
                $keepIds[] = $attribute->values()->create($payload)->id;
            }
        }

        // Remove values the admin deleted from the form (detaching variant links first).
        $attribute->values()
            ->whereNotIn('id', $keepIds)
            ->get()
            ->each(function ($value) {
                $value->variants()->detach();
                $value->delete();
            });
    }
}
