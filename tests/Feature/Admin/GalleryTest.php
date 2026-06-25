<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Gallery\MediaLibrary;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::role('super-admin')->first()
            ?? User::where('email', 'admin@usman-ecommerce.test')->firstOrFail();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/gallery')->assertRedirect(route('login'));
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get('/admin/gallery')->assertForbidden();
    }

    public function test_admin_can_open_the_gallery(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/gallery')
            ->assertOk()
            ->assertSeeLivewire(MediaLibrary::class);
    }

    public function test_uploading_an_image_creates_a_media_record(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(MediaLibrary::class)
            ->set('uploads', [UploadedFile::fake()->image('photo.png', 120, 90)])
            ->assertHasNoErrors();

        $media = Media::where('uploaded_by', $admin->id)->where('title', 'photo.png')->first();

        $this->assertNotNull($media, 'Media row was not created.');
        $this->assertSame('public', $media->disk);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_uploading_a_non_image_is_rejected(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(MediaLibrary::class)
            ->set('uploads', [UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf')])
            ->assertHasErrors('uploads.*');
    }

    public function test_selecting_renders_the_detail_panel(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $path = UploadedFile::fake()->image('sel.png')->store('gallery', 'public');
        $media = Media::create([
            'disk' => 'public', 'path' => $path, 'mime' => 'image/png',
            'size' => 50, 'title' => 'sel.png', 'folder' => 'gallery', 'uploaded_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(MediaLibrary::class)
            ->call('select', $media->id)
            ->assertSet('selectedId', $media->id)
            ->assertSee('Details')
            ->assertSee('Full URL');
    }

    public function test_toggling_to_list_view_renders_a_table(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        UploadedFile::fake()->image('a.png')->store('gallery', 'public');
        Media::create(['disk' => 'public', 'path' => 'gallery/a.png', 'mime' => 'image/png', 'size' => 10, 'title' => 'a.png', 'uploaded_by' => $admin->id]);

        Livewire::actingAs($admin)
            ->test(MediaLibrary::class)
            ->set('view', 'list')
            ->assertSet('view', 'list')
            ->assertSeeHtml('<table');
    }

    public function test_deleting_removes_the_record_and_file(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $path = UploadedFile::fake()->image('gone.png')->store('gallery', 'public');
        $media = Media::create([
            'disk' => 'public', 'path' => $path, 'mime' => 'image/png',
            'size' => 1234, 'title' => 'gone.png', 'folder' => 'gallery', 'uploaded_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(MediaLibrary::class)
            ->call('delete', $media->id);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
