{{-- Site-logo watermark, repeated on every PDF page. `position: fixed` makes
     dompdf paint it on each page; it sits first in <body> so all content
     renders over it, and the low opacity keeps tables readable. The image is
     inlined as base64 because dompdf reads no app URLs. No logo set → no
     watermark. --}}
@php
    $watermarkData = null;
    if ($logoId = setting('general', 'logo')) {
        try {
            $logoMedia = \App\Models\Media::find($logoId);
            $logoDisk = $logoMedia ? \Illuminate\Support\Facades\Storage::disk($logoMedia->disk) : null;
            if ($logoDisk?->exists($logoMedia->path)) {
                $watermarkData = 'data:' . ($logoMedia->mime ?: 'image/png') . ';base64,'
                    . base64_encode($logoDisk->get($logoMedia->path));
            }
        } catch (\Throwable) {
            // A broken logo must never break an export.
        }
    }
@endphp
@if ($watermarkData)
    <div style="position: fixed; top: 34%; left: 0; right: 0; text-align: center; opacity: 0.05;">
        <img src="{{ $watermarkData }}" style="width: 340px; max-width: 55%;">
    </div>
@endif
