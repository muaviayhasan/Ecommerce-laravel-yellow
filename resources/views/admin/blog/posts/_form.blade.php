@php
    $cell = 'w-full bg-surface-container-low border border-outline-variant rounded-lg px-3 py-2 text-sm text-on-surface placeholder:text-outline focus:ring-1 focus:ring-primary focus:border-primary outline-none';
    $selCats = old('categories', $selectedCategories);
    $selTags = old('tags', $selectedTags);
@endphp

<div class="grid grid-cols-12 gap-6 items-start">
    <div class="col-span-12 lg:col-span-8 space-y-6">
        <x-settings.section title="Content">
            <div class="space-y-5">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Title <span class="text-error">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $post->title) }}" maxlength="255" class="{{ $cell }}">
                    @error('title')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $post->slug) }}" maxlength="255" placeholder="Leave blank to auto-generate" class="{{ $cell }}">
                    @error('slug')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Excerpt</label>
                    <textarea name="excerpt" rows="2" maxlength="500" class="{{ $cell }} resize-y" placeholder="Short summary shown in listings (optional)">{{ old('excerpt', $post->excerpt) }}</textarea>
                    @error('excerpt')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Body <span class="text-error">*</span></label>
                    <textarea id="post-body" name="body" rows="16" class="{{ $cell }} resize-y">{{ old('body', $post->body) }}</textarea>
                    <p class="text-xs text-outline">Format with the toolbar — the <span class="font-mono">&lt;/&gt;</span> button edits raw HTML.</p>
                    @error('body')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-settings.section>

        <x-settings.section title="SEO">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Meta title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" maxlength="255" class="{{ $cell }}">
                </div>
                <div class="md:row-span-2 space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">OG image</label>
                    <x-settings.media-picker id="og_image_media_id" name="og_image_media_id" :selected="old('og_image_media_id', $post->og_image_media_id)" :media="$mediaItems" placeholder="Choose a share image" />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Meta description</label>
                    <textarea name="meta_description" rows="3" maxlength="255" class="{{ $cell }} resize-y">{{ old('meta_description', $post->meta_description) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <x-settings.toggle id="no_index" name="no_index" label="Hide from search engines" description="Adds a noindex tag for this post." :checked="(bool) old('no_index', $post->no_index)" />
                </div>
            </div>
        </x-settings.section>
    </div>

    <div class="col-span-12 lg:col-span-4 space-y-6">
        <x-settings.section title="Publishing">
            <div class="space-y-5">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Status</label>
                    <select name="status" class="{{ $cell }} cursor-pointer">
                        <option value="draft" @selected(old('status', $post->status) === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $post->status) === 'published')>Published</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Publish date</label>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="{{ $cell }}">
                    <p class="text-xs text-outline">Blank = stamped automatically when first published.</p>
                    @error('published_at')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-settings.section>

        <x-settings.section title="Cover image">
            <x-settings.media-picker id="cover_media_id" name="cover_media_id" :selected="old('cover_media_id', $post->cover_media_id)" :media="$mediaItems" placeholder="Choose a cover image" />
        </x-settings.section>

        <x-settings.section title="Taxonomy">
            <div class="space-y-5">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Categories</label>
                    <select name="categories[]" multiple class="{{ $cell }}" data-placeholder="Choose categories">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(in_array($cat->id, $selCats ?? []))>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @if ($categories->isEmpty())<p class="text-xs text-outline"><a href="{{ route('admin.blog.categories.index') }}" class="text-primary hover:underline">Add categories</a> first.</p>@endif
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-on-surface-variant">Tags</label>
                    <select name="tags[]" multiple class="{{ $cell }}" data-placeholder="Choose tags">
                        @foreach ($tags as $tag)
                            <option value="{{ $tag->id }}" @selected(in_array($tag->id, $selTags ?? []))>{{ $tag->name }}</option>
                        @endforeach
                    </select>
                    @if ($tags->isEmpty())<p class="text-xs text-outline"><a href="{{ route('admin.blog.tags.index') }}" class="text-primary hover:underline">Add tags</a> first.</p>@endif
                </div>
            </div>
        </x-settings.section>
    </div>
</div>

@push('scripts')
    {{-- Rich-text editor for the Body field (self-hosted TinyMCE via CDN — no API key).
         Falls back to the plain HTML textarea if the script can't load. --}}
    <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        (function () {
            function initBodyEditor() {
                if (typeof tinymce === 'undefined') return;               // offline → keep plain textarea
                if (!document.getElementById('post-body')) return;
                if (tinymce.get('post-body')) return;                     // already initialised
                tinymce.init({
                    selector: 'textarea#post-body',
                    plugins: 'lists link code table autolink',
                    toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link table | code removeformat',
                    menubar: false,
                    branding: false,
                    promotion: false,
                    height: 500,
                    content_style: 'body{font-family:Inter,system-ui,sans-serif;font-size:15px;line-height:1.7}',
                    // Keep the underlying <textarea name="body"> in sync so validation + submit work.
                    setup: function (editor) {
                        editor.on('change keyup', function () { editor.save(); });
                    },
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initBodyEditor);
            } else {
                initBodyEditor();
            }
            // Play nice with Livewire SPA navigation, if used.
            document.addEventListener('livewire:navigated', initBodyEditor);
            document.addEventListener('livewire:navigating', function () {
                if (typeof tinymce !== 'undefined') tinymce.remove('#post-body');
            });
        })();
    </script>
@endpush
