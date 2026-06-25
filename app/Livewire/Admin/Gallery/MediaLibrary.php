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

    public ?int $selectedId = null;

    /** Pending uploads bound to the file input (processed in updatedUploads). */
    public array $uploads = [];

    // Inline-edit fields for the selected asset.
    public string $editTitle = '';

    public string $editAlt = '';

    public function mount(): void
    {
        $this->authorize('gallery.view');
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

    /**
     * Persist each freshly-picked/dropped upload as a Media record on the public disk.
     */
    public function updatedUploads(): void
    {
        $this->authorize('gallery.create');

        $this->validate(
            ['uploads.*' => ['image', 'max:5120']], // 5 MB each
            ['uploads.*.image' => 'Each file must be an image.', 'uploads.*.max' => 'Each image may not exceed 5 MB.'],
        );

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
        $this->uploads = [];
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
        ]);

        $media->update([
            'title' => $this->editTitle !== '' ? $this->editTitle : null,
            'alt' => $this->editAlt !== '' ? $this->editAlt : null,
        ]);

        session()->flash('gallery_status', 'Details saved.');
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
            ->paginate(per_page());
    }

    public function render(): View
    {
        return view('livewire.admin.gallery.media-library');
    }
}
