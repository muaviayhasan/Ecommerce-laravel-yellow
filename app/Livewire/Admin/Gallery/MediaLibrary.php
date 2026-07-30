<?php

namespace App\Livewire\Admin\Gallery;

use App\Models\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Admin media library ("Gallery"). Manages the `media` table: upload, browse,
 * search/sort/filter, inline-edit metadata, delete. Guarded by gallery.* (§4.1);
 * the wrapping route adds `can:gallery.view`, and each mutating action re-checks.
 */
class MediaLibrary extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $sort = 'recent';

    public string $folder = '';

    /** Layout of the asset list: 'grid' | 'list'. */
    public string $view = 'grid';

    /** Assets per page — starts at the configured page size (§6.1). */
    public int $perPage = 15;

    public ?int $selectedId = null;

    /** Whether the upload panel is open. */
    public bool $showUploader = false;

    /** Files staged in the uploader (previewed, not yet saved to the gallery). */
    public array $uploads = [];

    // Inline-edit fields for the selected asset.
    public string $editTitle = '';

    public string $editAlt = '';

    /** File name (without extension) — renaming moves the file on disk. */
    public string $editFilename = '';

    public function mount(): void
    {
        $this->authorize('gallery.view');
        $this->perPage = per_page();
    }

    // Filters reset pagination so you never land on an empty page.
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedFolder(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        // Snap anything hand-crafted back to an offered size.
        if (! in_array($this->perPage, $this->perPageOptions, true)) {
            $this->perPage = per_page();
        }

        $this->resetPage();
    }

    /**
     * Sizes offered in the per-page dropdown — the standard ladder plus the
     * configured default, so it's always selectable (mirrors x-admin.per-page).
     *
     * @return array<int, int>
     */
    #[Computed]
    public function perPageOptions(): array
    {
        return collect([15, 25, 50, 100])->push(per_page())->unique()->sort()->values()->all();
    }

    /** Validation rules + messages shared by staging and saving. */
    protected function uploadRules(): array
    {
        return [
            ['uploads.*' => ['image', 'max:5120']], // 5 MB each
            [
                'uploads.*.image' => 'Each file must be an image.',
                'uploads.*.max' => 'Each image may not exceed 5 MB.',
                'uploads.*.uploaded' => 'An image failed to upload — it is likely larger than the server allows. Images must be 5 MB or smaller.',
            ],
        ];
    }

    /** Open the upload panel (fresh). */
    public function openUploader(): void
    {
        $this->authorize('gallery.create');
        $this->reset('uploads');
        $this->resetValidation();
        $this->showUploader = true;
    }

    /** Close the upload modal and discard anything staged. */
    public function closeUploader(): void
    {
        $this->reset('uploads', 'showUploader');
        $this->resetValidation();
    }

    /** Validate files as they're staged so problems surface before saving. */
    public function updatedUploads(): void
    {
        [$rules, $messages] = $this->uploadRules();
        $this->validate($rules, $messages);
    }

    /** Drop a single staged file before saving. (Not `removeUpload` — that's reserved by WithFileUploads.) */
    public function removeStaged(int $index): void
    {
        unset($this->uploads[$index]);
        $this->uploads = array_values($this->uploads);
    }

    /** Commit the staged uploads to the gallery as Media records (public disk). */
    public function save(): void
    {
        $this->authorize('gallery.create');

        if (empty($this->uploads)) {
            return;
        }

        [$rules, $messages] = $this->uploadRules();
        $this->validate($rules, $messages);

        foreach ($this->uploads as $file) {
            $path = $file->store('gallery', 'public');

            [$width, $height] = $this->dimensions($file->getRealPath());

            Media::create([
                'disk' => 'public',
                'path' => $path,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'title' => $file->getClientOriginalName(),
                'folder' => $this->folder !== '' ? $this->folder : 'gallery',
                'uploaded_by' => auth()->id(),
            ]);
        }

        $count = count($this->uploads);
        $this->reset('uploads', 'showUploader');
        $this->resetPage();
        unset($this->folders);

        session()->flash('gallery_status', "{$count} file(s) uploaded.");
    }

    public function select(int $id): void
    {
        $media = Media::find($id);

        if (! $media) {
            return;
        }

        $this->selectedId = $media->id;
        $this->editTitle = (string) $media->title;
        $this->editAlt = (string) $media->alt;
        $this->editFilename = pathinfo($media->path, PATHINFO_FILENAME);
    }

    public function deselect(): void
    {
        $this->selectedId = null;
    }

    public function saveMeta(): void
    {
        $this->authorize('gallery.edit');

        $media = Media::find($this->selectedId);

        if (! $media) {
            return;
        }

        $this->validate([
            'editTitle' => ['nullable', 'string', 'max:255'],
            'editAlt' => ['nullable', 'string', 'max:255'],
            'editFilename' => ['required', 'string', 'max:150'],
        ]);

        $renamed = $this->renameFile($media);

        $media->update([
            'title' => $this->editTitle !== '' ? $this->editTitle : null,
            'alt' => $this->editAlt !== '' ? $this->editAlt : null,
        ]);

        session()->flash('gallery_status', $renamed ? 'Details saved — file renamed.' : 'Details saved.');
    }

    /**
     * Move the file on disk when the (slugified) name changed. Thumbs are keyed
     * by media id, and every reference resolves via id → url, so a rename is
     * safe; only URLs hard-pasted into rich text would go stale.
     */
    private function renameFile(Media $media): bool
    {
        $base = \Illuminate\Support\Str::slug($this->editFilename) ?: 'file';
        $current = pathinfo($media->path, PATHINFO_FILENAME);

        if ($base === $current) {
            return false;
        }

        $dir = pathinfo($media->path, PATHINFO_DIRNAME);
        $ext = pathinfo($media->path, PATHINFO_EXTENSION);
        $prefix = ($dir !== '' && $dir !== '.') ? $dir . '/' : '';

        // Dodge collisions: name.webp, name-2.webp, …
        $candidate = "{$prefix}{$base}.{$ext}";
        for ($i = 2; Storage::disk($media->disk)->exists($candidate); $i++) {
            $candidate = "{$prefix}{$base}-{$i}.{$ext}";
        }

        Storage::disk($media->disk)->move($media->path, $candidate);
        $media->update(['path' => $candidate]);
        $this->editFilename = pathinfo($candidate, PATHINFO_FILENAME);

        return true;
    }

    public function delete(int $id): void
    {
        $this->authorize('gallery.delete');

        $media = Media::find($id);

        if (! $media) {
            return;
        }

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        if ($this->selectedId === $id) {
            $this->selectedId = null;
        }

        unset($this->folders);
        session()->flash('gallery_status', 'File deleted.');
    }

    /** Read image dimensions from the uploaded temp file (best effort). */
    private function dimensions(?string $path): array
    {
        if ($path && is_file($path) && ($info = @getimagesize($path))) {
            return [$info[0], $info[1]];
        }

        return [null, null];
    }

    /** Distinct folders for the filter dropdown. */
    #[Computed]
    public function folders(): Collection
    {
        return Media::query()
            ->whereNotNull('folder')
            ->distinct()
            ->orderBy('folder')
            ->pluck('folder');
    }

    #[Computed]
    public function selected(): ?Media
    {
        return $this->selectedId ? Media::with('uploader')->find($this->selectedId) : null;
    }

    /**
     * Where each asset on the current page is referenced — drives the "In use"
     * badge.
     *
     * @return array<int, array<string, int>>
     */
    #[Computed]
    public function usage(): array
    {
        return Media::usageFor($this->media->pluck('id')->all());
    }

    /**
     * Usage of the selected asset — looked up on its own, since the detail panel
     * can outlive the page its asset was on.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function selectedUsage(): array
    {
        if (! $this->selectedId) {
            return [];
        }

        return Media::usageFor([$this->selectedId])[$this->selectedId] ?? [];
    }

    #[Computed]
    public function media(): LengthAwarePaginator
    {
        return Media::query()
            ->with('uploader')
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';
                $query->where(fn ($q) => $q
                    ->where('title', 'like', $term)
                    ->orWhere('alt', 'like', $term)
                    ->orWhere('path', 'like', $term));
            })
            ->when($this->folder !== '', fn ($query) => $query->where('folder', $this->folder))
            ->tap(fn ($query) => match ($this->sort) {
                'oldest' => $query->oldest('id'),
                'name_asc' => $query->orderBy('title'),
                'name_desc' => $query->orderByDesc('title'),
                'largest' => $query->orderByDesc('size'),
                default => $query->latest('id'),
            })
            ->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.admin.gallery.media-library');
    }
}
