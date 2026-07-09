<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'disk', 'path', 'mime', 'size', 'width', 'height',
        'alt', 'title', 'folder', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        $url = Storage::disk($this->disk)->url($this->path);

        // For same-origin local/public media, return a root-relative URL so images
        // resolve against whatever host:port serves the page — independent of APP_URL
        // (e.g. `php artisan serve` on a non-default port). Remote disks (s3, …) keep
        // their absolute URL.
        if (in_array($this->disk, ['public', 'local'], true)) {
            return parse_url($url, PHP_URL_PATH) ?: $url;
        }

        return $url;
    }

    /**
     * URL of a downscaled WebP rendition, generated on first use and cached on
     * the same disk under thumbs/. Uploads are often 500x500+ PNGs shown in
     * ~100-200px slots; serving a right-sized WebP cuts most of the bytes
     * (Lighthouse "Improve image delivery"). Falls back to the original URL
     * whenever a rendition can't be produced (remote disk, SVG/GIF, no GD, …).
     *
     * $width is the box the image must fit in (pass the 2x retina size of the
     * display slot); images are never upscaled.
     */
    public function thumbUrl(int $width): string
    {
        if (! in_array($this->disk, ['public', 'local'], true)
            || in_array($this->mime, ['image/svg+xml', 'image/gif'], true)
            || ! function_exists('imagewebp')) {
            return $this->url;
        }

        $disk = Storage::disk($this->disk);
        $thumbPath = "thumbs/{$this->id}-w{$width}.webp";

        try {
            if (! $disk->exists($thumbPath) && ! $this->generateThumb($width, $thumbPath)) {
                return $this->url;
            }
        } catch (\Throwable) {
            return $this->url;
        }

        $url = $disk->url($thumbPath);

        return parse_url($url, PHP_URL_PATH) ?: $url;
    }

    /** Render the original down to a WebP fitting in a $width box. */
    private function generateThumb(int $width, string $thumbPath): bool
    {
        $disk = Storage::disk($this->disk);

        $raw = $disk->get($this->path);
        if ($raw === null || ($src = @imagecreatefromstring($raw)) === false) {
            return false;
        }

        if (! imageistruecolor($src)) {
            imagepalettetotruecolor($src);
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, $width / max($w, $h)); // fit inside the box, never upscale
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($tw, $th);
        // Keep PNG transparency (product cut-outs) intact in the WebP.
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

        ob_start();
        $ok = imagewebp($dst, null, 80);
        $webp = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $ok && $webp !== false && $disk->put($thumbPath, $webp);
    }
}
