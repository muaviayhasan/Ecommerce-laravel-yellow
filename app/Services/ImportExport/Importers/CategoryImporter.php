<?php

namespace App\Services\ImportExport\Importers;

use App\Models\Category;
use App\Models\Media;
use App\Services\ImportExport\Exporters\CategoryExporter;
use App\Services\ImportExport\ImportResult;
use App\Services\ImportExport\SpreadsheetReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Upserts categories from an .xlsx (headers = CategoryExporter::HEADERS).
 * Match key: slug. Parents resolve in a second pass so child rows may appear
 * before (or reference) categories created later in the same file.
 *
 * Validation mirrors App\Http\Requests\Admin\CategoryRequest — kept in sync by
 * hand because prepareForValidation/route-bound ignores can't run here.
 */
class CategoryImporter
{
    public function __construct(private readonly SpreadsheetReader $reader)
    {
    }

    public function import(string $path): ImportResult
    {
        $result = new ImportResult();

        /** @var array<int, array{category: Category, parent_slug: string}> $pending row => resolution data */
        $pending = [];
        /** @var array<string, Category> $bySlug everything touched this run, for pass-2 lookups */
        $bySlug = [];

        foreach ($this->reader->rows($path, CategoryExporter::HEADERS) as $rowNumber => $row) {
            try {
                $category = DB::transaction(fn () => $this->upsertRow($row, $result, $rowNumber));
            } catch (RowError $e) {
                $result->error($rowNumber, $e->getMessage());

                continue;
            }

            $bySlug[$category->slug] = $category;

            if ($row['parent_slug'] !== '') {
                $pending[$rowNumber] = ['category' => $category, 'parent_slug' => $row['parent_slug']];
            }
        }

        // Pass 2 — parents (may have been created later in the file).
        foreach ($pending as $rowNumber => $link) {
            $parent = $bySlug[$link['parent_slug']] ?? Category::where('slug', $link['parent_slug'])->first();

            if (! $parent) {
                $result->warning($rowNumber, "Parent category '{$link['parent_slug']}' was not found — '{$link['category']->name}' was saved without a parent.");

                continue;
            }
            if ($parent->id === $link['category']->id) {
                $result->warning($rowNumber, "'{$link['category']->name}' cannot be its own parent — saved without a parent.");

                continue;
            }

            $link['category']->update(['parent_id' => $parent->id]);
        }

        return $result;
    }

    /** @throws RowError */
    private function upsertRow(array $row, ImportResult $result, int $rowNumber): Category
    {
        $slug = Str::slug($row['slug'] !== '' ? $row['slug'] : $row['name']);
        $existing = $slug !== '' ? Category::where('slug', $slug)->first() : null;

        // Blank cells never overwrite an existing value (clear fields in the
        // category form instead); on create they fall back to defaults.
        $keep = fn (string $col, mixed $default = null) => $row[$col] !== '' ? $row[$col] : ($existing?->{$col} ?? $default);

        $data = [
            'name' => $row['name'] !== '' ? $row['name'] : ($existing->name ?? ''),
            'description' => $keep('description'),
            'sort_order' => $keep('sort_order', 0),
            'markup_percent' => $keep('markup_percent'),
            'meta_title' => $keep('meta_title'),
            'meta_description' => $keep('meta_description'),
        ];

        // Booleans: blank keeps the existing value (create: column default true).
        $isActive = SpreadsheetReader::bool($row['is_active']);
        if ($isActive !== null || ! $existing) {
            $data['is_active'] = $isActive ?? ($existing->is_active ?? true);
        }

        // Rules mirror CategoryRequest (parent handled in pass 2, slug via generation below).
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'markup_percent' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new RowError($validator->errors()->first());
        }

        if ($row['image'] !== '') {
            $mediaId = Media::where('path', $row['image'])->value('id');
            if ($mediaId === null) {
                $result->warning($rowNumber, "Image '{$row['image']}' not found in the gallery — skipped for this category.");
            } else {
                $data['image_media_id'] = $mediaId;
            }
        }

        if ($existing) {
            $existing->update($data);
            $result->updated++;

            return $existing;
        }

        $data['slug'] = $this->uniqueSlug($slug ?: Str::slug($row['name']));
        $category = Category::create($data);
        $result->created++;

        return $category;
    }

    /** Replicates CategoryRequest::uniqueSlug for rows creating a new category. */
    private function uniqueSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'category';
        $slug = $base;
        for ($i = 2; Category::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
