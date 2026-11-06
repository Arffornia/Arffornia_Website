<?php

namespace Tests\Feature;

use App\Models\LauncherImage;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LauncherImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Stage::factory()->create();
        $this->admin = User::factory()->create(['role' => 'user,admin']);
    }

    public function test_admin_can_upload_an_image_stored_under_a_generated_name(): void
    {
        $file = UploadedFile::fake()->image('My Launcher.png', 64, 64);

        $this->actingAs($this->admin)
            ->post('/admin/launcherImages', ['launcher_file' => $file, 'in_prod' => '1'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $image = LauncherImage::sole();
        $this->assertTrue((bool) $image->in_prod);
        $this->assertStringStartsWith('storage/uploads/launcherImages/', $image->path);
        $this->assertStringNotContainsString('My', $image->path);
        $this->assertStringEndsWith('.png', $image->path);
        Storage::disk('public')->assertExists(substr($image->path, strlen('storage/')));
    }

    public function test_non_image_files_are_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;');

        $this->actingAs($this->admin)
            ->post('/admin/launcherImages', ['launcher_file' => $file])
            ->assertSessionHasErrors('launcher_file');

        $this->assertSame(0, LauncherImage::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_non_image_with_an_image_extension_is_rejected(): void
    {
        // Real temporary file so the MIME type is sniffed from the content (fake files report it from the name)
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, 'not really an image');
        $file = new UploadedFile($path, 'fake.png', null, null, true);

        $this->actingAs($this->admin)
            ->post('/admin/launcherImages', ['launcher_file' => $file])
            ->assertSessionHasErrors('launcher_file');

        $this->assertSame(0, LauncherImage::count());
    }

    public function test_svg_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $this->actingAs($this->admin)
            ->post('/admin/launcherImages', ['launcher_file' => $file])
            ->assertSessionHasErrors('launcher_file');
    }

    public function test_files_over_2_mb_are_rejected(): void
    {
        $file = UploadedFile::fake()->image('big.png')->size(3000);

        $this->actingAs($this->admin)
            ->post('/admin/launcherImages', ['launcher_file' => $file])
            ->assertSessionHasErrors('launcher_file');
    }

    public function test_non_admin_cannot_upload(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $file = UploadedFile::fake()->image('a.png');

        $response = $this->actingAs($user)->post('/admin/launcherImages', ['launcher_file' => $file]);

        $this->assertContains($response->status(), [302, 403]);
        $this->assertSame(0, LauncherImage::count());
    }
}
