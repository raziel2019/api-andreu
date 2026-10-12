<?php

namespace Tests\Feature;

use App\Models\AboutPage;
use App\Models\HomePage;
use App\Models\HomeServiceSummary;
use App\Models\Service;
use App\Models\ServicePage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CmsMedia;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_library_shows_real_media_and_dynamic_service_slots(): void
    {
        $admin = $this->cmsAdmin();
        $service = Service::factory()->create(['title' => ['ca' => '<script>alert(1)</script>', 'es' => 'Servicio']]);

        $this->actingAs($admin)->get('/admin/media')->assertViewHas('slots', fn (array $slots): bool => isset($slots['service-'.$service->id], $slots['feature-1'], $slots['interface-contact_message']))
            ->assertSee('seed/home-hero.png')->assertSee('seed/about.png')->assertSee('seed/documents.png')->assertSee('seed/logo.svg')
            ->assertSee(route('admin.media.update', 'service-'.$service->id))->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('Blog');
    }

    public static function mediaSlots(): array
    {
        return [
            'home hero' => ['home-hero', HomePage::class, 'hero_image_path'],
            'home portfolio' => ['home-portfolio', HomePage::class, 'portfolio_image_path'],
            'about' => ['about-image', AboutPage::class, 'image_path'],
            'services page' => ['services-image', ServicePage::class, 'image_path'],
            'logo' => ['site-logo', SiteSetting::class, 'logo_path'],
            'service' => ['service-1', Service::class, 'icon_path'],
            'summary' => ['summary-1', HomeServiceSummary::class, 'icon_path'],
        ];
    }

    #[DataProvider('mediaSlots')]
    public function test_library_upload_updates_only_the_existing_media_field(string $slot, string $model, string $column): void
    {
        $admin = $this->cmsAdmin();
        $record = $model::findOrFail(1);
        $before = $record->getAttributes();

        $this->actingAs($admin)->put('/admin/media/'.$slot, ['image' => UploadedFile::fake()->image('replacement.png'), 'active' => false, $column => 'injected.php'])->assertRedirect('/admin/media')->assertSessionHas('status');

        $after = $record->fresh()->getAttributes();
        $path = $after[$column];
        $this->assertStringStartsWith('cms/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString('/storage/cms/', Storage::disk('public')->url($path));
        unset($before[$column], $before['updated_at'], $after[$column], $after['updated_at']);
        if ($model === HomeServiceSummary::class) {
            $this->assertNull($after['icon']);
            unset($before['icon'], $after['icon']);
            $this->getJson('/api/v1/pages/home')->assertJsonPath('data.service_summaries.0.icon', null)
                ->assertJsonPath('data.service_summaries.0.icon_url', Storage::disk('public')->url($path));
        }
        $this->assertSame($before, $after);
    }

    public function test_successive_replacement_retains_library_assets_and_updates_api_url(): void
    {
        $admin = $this->cmsAdmin();
        $this->actingAs($admin)->put('/admin/media/about-image', ['image' => UploadedFile::fake()->image('first.jpg')])->assertRedirect('/admin/media');
        $old = AboutPage::findOrFail(1)->image_path;

        $this->put('/admin/media/about-image', ['image' => UploadedFile::fake()->image('second.png')])->assertRedirect('/admin/media');

        $path = AboutPage::findOrFail(1)->image_path;
        Storage::disk('public')->assertExists($old);
        Storage::disk('public')->assertExists($path);
        $this->getJson('/api/v1/pages/about')->assertJsonPath('data.image_url', Storage::disk('public')->url($path));
    }

    public function test_shared_image_is_preserved_when_one_slot_is_replaced(): void
    {
        $admin = $this->cmsAdmin();
        AboutPage::findOrFail(1)->update(['image_path' => HomePage::findOrFail(1)->hero_image_path]);

        $this->actingAs($admin)->put('/admin/media/about-image', ['image' => UploadedFile::fake()->image('replacement.jpg')])->assertRedirect('/admin/media');

        Storage::disk('public')->assertExists('seed/home-hero.png');
        $this->assertSame('seed/home-hero.png', HomePage::findOrFail(1)->hero_image_path);
    }

    public static function invalidImages(): array
    {
        return [['missing'], ['svg'], ['disguised'], ['size'], ['dimensions']];
    }

    #[DataProvider('invalidImages')]
    public function test_invalid_replacement_preserves_existing_database_path_and_image(string $kind): void
    {
        $admin = $this->cmsAdmin();
        $file = match ($kind) {
            'missing' => null,
            'svg' => UploadedFile::fake()->createWithContent('active.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'disguised' => UploadedFile::fake()->image('image.php'),
            'size' => UploadedFile::fake()->image('large.png')->size(5121),
            'dimensions' => UploadedFile::fake()->image('wide.png', 6001, 1),
        };

        $this->actingAs($admin)->put('/admin/media/about-image', ['image' => $file])->assertSessionHasErrors('image');

        $this->assertSame('seed/about.png', AboutPage::findOrFail(1)->image_path);
        Storage::disk('public')->assertExists('seed/about.png');
        $this->assertSame([], Storage::disk('public')->files('cms'));
    }

    public function test_upload_failure_preserves_existing_image(): void
    {
        $admin = $this->cmsAdmin();
        $this->mock(CmsMedia::class)->shouldReceive('store')->once()->andThrow(ValidationException::withMessages(['image' => 'No s’ha pogut desar la imatge. Torna-ho a provar.']));

        $this->actingAs($admin)->put('/admin/media/about-image', ['image' => UploadedFile::fake()->image('new.png')])->assertSessionHasErrors(['image' => 'No s’ha pogut desar la imatge. Torna-ho a provar.']);

        $this->assertSame('seed/about.png', AboutPage::findOrFail(1)->image_path);
        Storage::disk('public')->assertExists('seed/about.png');
    }

    public function test_unknown_or_deleted_slots_return_404_without_creating_files(): void
    {
        $admin = $this->cmsAdmin();

        $this->actingAs($admin)->put('/admin/media/unknown', ['image' => UploadedFile::fake()->image('new.png')])->assertNotFound();
        $this->put('/admin/media/service-99999', ['image' => UploadedFile::fake()->image('new.png')])->assertNotFound();

        $this->assertSame([], Storage::disk('public')->files('cms'));
    }

    public function test_guest_and_non_admin_cannot_replace_media(): void
    {
        $admin = $this->cmsAdmin();

        $this->put('/admin/media/about-image', ['image' => UploadedFile::fake()->image('new.png')])->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->put('/admin/media/about-image', ['image' => UploadedFile::fake()->image('new.png')])->assertForbidden();

        $this->assertSame('seed/about.png', AboutPage::findOrFail(1)->image_path);
        $this->assertSame([], Storage::disk('public')->files('cms'));
    }

    public function test_upload_requires_csrf(): void
    {
        $admin = $this->cmsAdmin();
        $this->app['env'] = 'local';

        $this->actingAs($admin)->put('/admin/media/about-image', ['image' => UploadedFile::fake()->image('new.png')])->assertStatus(419);

        $this->assertSame('seed/about.png', AboutPage::findOrFail(1)->image_path);
    }
}
