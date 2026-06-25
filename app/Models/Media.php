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
}
