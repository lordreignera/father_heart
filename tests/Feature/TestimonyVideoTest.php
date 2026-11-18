<?php

namespace Tests\Feature;

use App\Models\TestimonyVideo;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TestimonyVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_and_play_a_private_testimony(): void
    {
        Storage::fake('local');
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create()->assignRole('Super Administrator');
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Testimony videos');
        $this->get(route('admin.testimonies.index'))->assertOk()->assertSee('Upload video');
        $this->post(route('admin.testimonies.store'), ['title' => 'Example testimony', 'description' => 'Private example', 'video' => UploadedFile::fake()->create('example.mp4', 12, 'video/mp4')])->assertRedirect()->assertSessionHasNoErrors();
        $video = TestimonyVideo::firstOrFail();
        Storage::disk('local')->assertExists($video->file_path);
        $this->assertStringStartsWith('testimony-videos/', $video->file_path);
        $this->get(route('admin.testimonies.index'))->assertOk()->assertSee('Example testimony')->assertDontSee($video->file_path);
        $this->get(route('admin.testimonies.show', $video))->assertOk()->assertHeader('Content-Type', 'video/mp4')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_video_access_and_upload_require_individual_permissions(): void
    {
        Storage::fake('local');
        $this->seed(RoleSeeder::class);
        Storage::disk('local')->put('testimony-videos/example.mp4', 'example');
        $video = TestimonyVideo::create(['title' => 'Private', 'file_path' => 'testimony-videos/example.mp4', 'mime_type' => 'video/mp4', 'file_size' => 7]);
        $this->get(route('admin.testimonies.show', $video))->assertRedirect(route('login'));
        $user = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-testimonies-index']);
        $this->actingAs($user)->get(route('admin.testimonies.index'))->assertOk()->assertDontSee('Upload video');
        $this->get(route('admin.testimonies.show', $video))->assertForbidden();
        $this->post(route('admin.testimonies.store'), [])->assertForbidden();
        $user->givePermissionTo('route-admin-testimonies-show');
        $this->get(route('admin.testimonies.show', $video))->assertOk();
        $other = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-testimonies-show']);
        $this->actingAs($other)->get(route('admin.testimonies.show', $video))->assertForbidden();
    }

    public function test_invalid_upload_is_rejected_and_no_record_is_created(): void
    {
        Storage::fake('local');
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create()->assignRole('Super Administrator');
        $this->actingAs($user)->post(route('admin.testimonies.store'), ['title' => 'Not a video', 'video' => UploadedFile::fake()->create('example.txt', 1, 'text/plain')])->assertSessionHasErrors('video');
        $this->assertDatabaseCount('testimony_videos', 0);
    }
}
