<?php

namespace Tests\Feature;

use App\Models\AboutPage;
use App\Models\HomePage;
use App\Models\MediaAsset;
use App\Models\Service;
use App\Models\ServiceFeature;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CmsMedia;
use App\Services\CmsMediaSlots;
use App\Services\MediaCatalog;
use Database\Seeders\ApprovedContentSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UniversalMediaTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function upload(string $extension = 'png'): MediaAsset
    {
        $file = UploadedFile::fake()->image('custom.'.($extension === 'webp' ? 'png' : $extension));
        if ($extension === 'webp') {
            $image = new \Imagick;
            $image->readImageBlob(file_get_contents($file->getRealPath()));
            $image->setImageFormat('webp');
            $file = UploadedFile::fake()->createWithContent('custom.webp', $image->getImageBlob());
            $image->clear();
        }
        $this->post('/admin/media', ['image' => $file, 'alt' => ['ca' => 'Icona', 'es' => 'Icono']])->assertRedirect('/admin/media');

        return MediaAsset::orderByDesc('id')->firstOrFail();
    }

    private function homePayload(): array
    {
        $home = HomePage::with('serviceSummaries')->findOrFail(1);

        return [...Arr::except($home->attributesToArray(), ['id', 'created_at', 'updated_at', 'hero_image_path', 'portfolio_image_path', 'service_summaries']),
            'service_summaries' => $home->serviceSummaries->map(fn ($summary): array => $summary->only(['slug', 'title', 'description', 'icon', 'sort_order']))->all()];
    }

    public static function formats(): array
    {
        return [['png', 'image/png'], ['jpg', 'image/jpeg'], ['webp', 'image/webp']];
    }

    #[DataProvider('formats')]
    public function test_authorized_uploads_create_reusable_raster_assets(string $extension, string $mime): void
    {
        $admin = $this->cmsAdmin();
        $this->actingAs($admin);

        $asset = $this->upload($extension);

        $this->assertSame($mime, $asset->mime_type);
        $this->assertSame(['ca' => 'Icona', 'es' => 'Icono'], $asset->alt);
        $this->assertSame(10, $asset->width);
        $this->assertSame(10, $asset->height);
        Storage::disk('public')->assertExists($asset->path);
        $this->assertStringContainsString('/storage/cms/', $asset->url());
        $this->assertMatchesRegularExpression('~^cms/[a-f0-9-]+\.(png|jpg|webp)$~', $asset->path);
    }

    public function test_library_upload_requires_admin_and_csrf(): void
    {
        $this->cmsAdmin();
        $count = MediaAsset::count();

        $this->post('/admin/media', ['image' => UploadedFile::fake()->image('new.png')])->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->post('/admin/media', ['image' => UploadedFile::fake()->image('new.png')])->assertForbidden();
        $this->app['env'] = 'local';
        $this->actingAs(User::factory()->administrator()->create())->post('/admin/media', ['image' => UploadedFile::fake()->image('new.png')])->assertStatus(419);

        $this->assertSame($count, MediaAsset::count());
        $this->assertSame([], Storage::disk('public')->files('cms'));
    }

    public static function unsafeContent(): array
    {
        return [
            'script' => ['active.svg', '<svg><script>alert(1)</script></svg>'],
            'event' => ['active.svg', '<svg onload="alert(1)"/>'],
            'external' => ['active.svg', '<svg><image href="https://evil.example/x"/></svg>'],
            'foreignObject' => ['active.svg', '<svg><foreignObject><iframe/></foreignObject></svg>'],
            'unsafe URL' => ['active.svg', '<svg><a href="javascript:alert(1)"/></svg>'],
            'safe SVG is also rejected' => ['safe.svg', '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0L1 1"/></svg>'],
            'SVG disguised as PNG' => ['image.png', '<svg onload="alert(1)"/>'],
            'PHP' => ['image.php', '<?php echo 1;'],
            'HTML' => ['image.png', '<html><script>alert(1)</script></html>'],
            'broken raster' => ['broken.png', "\x89PNG\r\n\x1a\ninvalid"],
            'AVIF rejected' => ['image.avif', 'not an image'],
        ];
    }

    #[DataProvider('unsafeContent')]
    public function test_invalid_and_svg_uploads_leave_no_files_or_assets(string $filename, string $content): void
    {
        $admin = $this->cmsAdmin();
        $count = MediaAsset::count();

        $this->actingAs($admin)->post('/admin/media', ['image' => UploadedFile::fake()->createWithContent($filename, $content)])->assertSessionHasErrors('image');

        $this->assertSame($count, MediaAsset::count());
        $this->assertSame([], Storage::disk('public')->files('cms'));
    }

    public function test_disguised_raster_extensions_and_limits_are_rejected(): void
    {
        $admin = $this->cmsAdmin();
        $this->actingAs($admin)->post('/admin/media', ['image' => UploadedFile::fake()->image('image.php')])->assertSessionHasErrors('image');
        $this->post('/admin/media', ['image' => UploadedFile::fake()->image('large.png')->size(5121)])->assertSessionHasErrors('image');
        $this->post('/admin/media', ['image' => UploadedFile::fake()->image('wide.png', 6001, 1)])->assertSessionHasErrors('image');
        $this->post('/admin/media', ['image' => UploadedFile::fake()->image('pixels.png', 4001, 4000)])->assertSessionHasErrors('image');

        $this->assertSame([], Storage::disk('public')->files('cms'));
    }

    public function test_raster_reencoding_removes_appended_active_content(): void
    {
        $admin = $this->cmsAdmin();
        $png = UploadedFile::fake()->image('image.png');
        $bytes = file_get_contents($png->getRealPath()).'<?php echo "payload"; ?>';

        $this->actingAs($admin)->post('/admin/media', ['image' => UploadedFile::fake()->createWithContent('image.png', $bytes)])->assertRedirect('/admin/media');

        $asset = MediaAsset::orderByDesc('id')->firstOrFail();
        $this->assertStringNotContainsString('payload', Storage::disk('public')->get($asset->path));
    }

    public static function assignableSlots(): array
    {
        return [
            ['home-hero'], ['home-portfolio'], ['about-image'], ['services-image'], ['site-logo'], ['service-1'], ['summary-1'], ['feature-1'],
            ['interface-action_arrow'], ['interface-service_arrow'], ['interface-footer_arrow'], ['interface-contact_callout'], ['interface-contact_message'], ['interface-contact_location'],
            ['interface-menu_open'], ['interface-menu_close'], ['interface-email'], ['interface-linkedin'], ['interface-instagram'],
        ];
    }

    #[DataProvider('assignableSlots')]
    public function test_all_audited_slots_can_select_and_unlink_existing_assets(string $slot): void
    {
        $admin = $this->cmsAdmin();
        $this->actingAs($admin);
        $asset = $this->upload();
        $slots = app(CmsMediaSlots::class);
        $target = $slots->find($slot);
        $before = $target['record']->attributesToArray();

        $this->put('/admin/media/'.$slot, ['image_asset_id' => $asset->id])->assertRedirect('/admin/media');

        $target = $slots->find($slot);
        $this->assertSame($asset->path, $slots::path($target));
        $after = $target['record']->attributesToArray();
        foreach (['title', 'description', 'sort_order', 'active', 'content'] as $field) {
            if (array_key_exists($field, $before)) {
                $this->assertSame($before[$field], $after[$field]);
            }
        }
        $this->put('/admin/media/'.$slot, ['remove_image' => 1])->assertRedirect('/admin/media');
        $this->assertNull($slots::path($slots->find($slot)));
        Storage::disk('public')->assertExists($asset->path);
        $this->assertModelExists($asset);
    }

    public function test_sharing_replacement_and_unlinking_preserve_other_assignments_and_api_urls(): void
    {
        $admin = $this->cmsAdmin();
        $this->actingAs($admin);
        $asset = $this->upload();
        $replacement = $this->upload('jpg');
        $this->put('/admin/media/about-image', ['image_asset_id' => $asset->id])->assertRedirect();
        $this->put('/admin/media/home-hero', ['image_asset_id' => $asset->id])->assertRedirect();
        $this->put('/admin/media/about-image', ['image_asset_id' => $replacement->id])->assertRedirect();
        $this->put('/admin/media/about-image', ['remove_image' => 1])->assertRedirect();

        $this->assertSame($asset->path, HomePage::findOrFail(1)->hero_image_path);
        $this->getJson('/api/v1/pages/home?locale=ca')->assertJsonPath('data.hero_image_url', $asset->url());
        $this->getJson('/api/v1/pages/about?locale=es')->assertJsonPath('data.image_url', null);
        Storage::disk('public')->assertExists([$asset->path, $replacement->path]);
        $this->assertCount(2, Storage::disk('public')->files('cms'));
    }

    public function test_invalid_or_missing_asset_ids_preserve_assignment(): void
    {
        $admin = $this->cmsAdmin();
        $asset = MediaAsset::factory()->create();

        $this->actingAs($admin)->put('/admin/media/about-image', ['image_asset_id' => 999999])->assertSessionHasErrors('image_asset_id');
        $this->put('/admin/media/about-image', ['image_asset_id' => $asset->id])->assertSessionHasErrors('image_asset_id');
        $this->put('/admin/media/about-image', ['remove_image' => 0])->assertSessionHasErrors('image');

        $this->assertSame('seed/about.png', AboutPage::findOrFail(1)->image_path);
    }

    public function test_home_editor_selects_custom_icons_and_images_and_keeps_translations_and_sorting(): void
    {
        $admin = $this->cmsAdmin();
        $this->actingAs($admin);
        $asset = $this->upload();
        $payload = $this->homePayload();
        $payload['hero_image_asset_id'] = $asset->id;
        $payload['service_summaries'][0]['custom_icon_asset_id'] = $asset->id;

        $this->put('/admin/home', $payload)->assertRedirect('/admin/home');

        $summary = HomePage::findOrFail(1)->serviceSummaries()->first();
        $this->assertNull($summary->icon);
        $this->assertSame($asset->path, $summary->icon_path);
        $this->assertSame($payload['service_summaries'][0]['title'], $summary->title);
        $this->assertSame($payload['service_summaries'][0]['sort_order'], $summary->sort_order);
        $this->getJson('/api/v1/pages/home')->assertJsonPath('data.service_summaries.0.icon_url', $asset->url());
    }

    public function test_home_summary_upload_remove_and_preset_remain_available(): void
    {
        $admin = $this->cmsAdmin();
        $payload = $this->homePayload();
        $payload['service_summaries'][0]['custom_icon'] = UploadedFile::fake()->image('custom.png');
        $this->actingAs($admin)->put('/admin/home', $payload)->assertRedirect();
        $path = HomePage::findOrFail(1)->serviceSummaries()->first()->icon_path;
        $payload = $this->homePayload();
        $payload['service_summaries'][0]['remove_custom_icon'] = 1;
        $this->put('/admin/home', $payload)->assertRedirect();
        $this->assertNull(HomePage::findOrFail(1)->serviceSummaries()->first()->icon_path);
        $payload = $this->homePayload();
        $payload['service_summaries'][0]['icon'] = 'scan';
        $this->put('/admin/home', $payload)->assertRedirect();

        $this->assertSame('scan', HomePage::findOrFail(1)->serviceSummaries()->first()->icon);
        Storage::disk('public')->assertExists($path);
    }

    public function test_service_editor_assigns_feature_and_main_icons_without_changing_copy(): void
    {
        $admin = $this->cmsAdmin();
        $this->actingAs($admin);
        $asset = $this->upload();
        $service = Service::with('features')->findOrFail(1);
        $features = $service->features->map(fn ($feature): array => $feature->only(['id', 'content', 'sort_order']))->all();
        $features[0]['icon_asset_id'] = $asset->id;
        $payload = [...$this->contentPayload($service), 'features' => $features, 'icon_asset_id' => $asset->id];

        $this->put('/admin/services/1', $payload)->assertRedirect();

        $this->assertSame($asset->path, $service->fresh()->icon_path);
        $feature = ServiceFeature::findOrFail($features[0]['id']);
        $this->assertSame($asset->path, $feature->icon_path);
        $this->assertSame($features[0]['content'], $feature->content);
        $this->getJson('/api/v1/services')->assertJsonPath('data.0.features.0.icon_url', $asset->url());
    }

    public function test_settings_editor_assigns_shared_icons_and_preserves_global_content(): void
    {
        $admin = $this->cmsAdmin();
        $this->actingAs($admin);
        $asset = $this->upload();
        $before = SiteSetting::findOrFail(1)->callout_title;

        $this->put('/admin/settings', ['interface_contact_message_asset_id' => $asset->id, 'logo_asset_id' => $asset->id])->assertRedirect();

        $this->assertSame($before, SiteSetting::findOrFail(1)->callout_title);
        $this->getJson('/api/v1/site')->assertJsonPath('data.interface_media.contact_message', $asset->url())->assertJsonPath('data.logo_url', $asset->url());
    }

    public function test_legacy_import_and_seeding_are_idempotent_and_keep_existing_media(): void
    {
        $admin = $this->cmsAdmin();
        $this->actingAs($admin);
        $asset = $this->upload();
        $this->put('/admin/media/about-image', ['image_asset_id' => $asset->id])->assertRedirect();
        $count = MediaAsset::count();
        $this->artisan('cms:import-media')->assertSuccessful();
        $this->seed(ApprovedContentSeeder::class);
        $this->artisan('cms:import-media')->assertSuccessful();

        $this->assertSame($count, MediaAsset::count());
        $this->assertSame($asset->path, AboutPage::findOrFail(1)->image_path);
        Storage::disk('public')->assertExists([$asset->path, 'seed/logo.svg']);
        $this->assertSame(1, MediaAsset::where('path', 'seed/documents.png')->count());
        $this->assertSame('https://cdn.example.com/old.jpg', CmsMedia::publicUrl('https://cdn.example.com/old.jpg'));
        $this->assertNull(CmsMedia::publicUrl('/var/www/private.png'));
    }

    public function test_untrusted_legacy_svg_and_executable_paths_cannot_enter_library(): void
    {
        $this->cmsAdmin();
        Storage::disk('public')->put('cms/evil.svg', '<svg onload="alert(1)"/>');
        Storage::disk('public')->put('cms/evil.php', file_get_contents(database_path('seeders/media/home-hero.png')));

        $this->artisan('cms:import-media')->assertSuccessful();

        $this->assertDatabaseMissing('media_assets', ['path' => 'cms/evil.svg']);
        $this->assertDatabaseMissing('media_assets', ['path' => 'cms/evil.php']);
        $this->assertNull(app(MediaCatalog::class)->register('../secret.png'));
    }

    public function test_service_feature_upload_is_atomic_with_the_content_transaction(): void
    {
        $admin = $this->cmsAdmin();
        $count = MediaAsset::count();
        $service = Service::findOrFail(1);
        $payload = [...$this->contentPayload($service), 'features' => [
            ['content' => $this->bilingual(), 'sort_order' => 0, 'icon' => UploadedFile::fake()->image('new.png')],
        ]];
        ServiceFeature::creating(function (): void {
            throw new \RuntimeException('Simulated feature failure');
        });
        try {
            $this->actingAs($admin)->put('/admin/services/1', $payload)->assertStatus(500);
        } finally {
            ServiceFeature::flushEventListeners();
        }

        $this->assertSame([], Storage::disk('public')->files('cms'));
        $this->assertSame($count, MediaAsset::count());
        $this->assertSame(3, $service->features()->count());
    }

    public function test_upload_metadata_requires_ca_and_es_and_rejects_other_locales(): void
    {
        $admin = $this->cmsAdmin();

        $this->actingAs($admin)->post('/admin/media', ['image' => UploadedFile::fake()->image('new.png'), 'alt' => ['ca' => 'Només català']])->assertSessionHasErrors('alt.es');
        $this->post('/admin/media', ['image' => UploadedFile::fake()->image('new.png'), 'alt' => ['ca' => 'Català', 'es' => 'Castellano', 'en' => 'English']])->assertSessionHasErrors('alt');

        $this->assertSame([], Storage::disk('public')->files('cms'));
    }

    public function test_public_clients_cannot_upload_or_assign_media(): void
    {
        $this->cmsAdmin();

        $this->postJson('/api/v1/media', [])->assertNotFound();
        $this->postJson('/api/v1/services', [])->assertStatus(405);

        $this->assertSame([], Storage::disk('public')->files('cms'));
    }

    public function test_about_editor_selects_an_asset_without_replacing_translated_image_description(): void
    {
        $admin = $this->cmsAdmin();
        $this->actingAs($admin);
        $asset = $this->upload();
        $about = AboutPage::with(['benefits', 'workingMethodSteps'])->findOrFail(1);
        $payload = [...$this->contentPayload($about),
            'benefits' => $about->benefits->map(fn ($row): array => $row->only(['id', 'content', 'sort_order']))->all(),
            'working_method_steps' => $about->workingMethodSteps->map(fn ($row): array => $row->only(['id', 'content', 'sort_order']))->all(),
            'image_asset_id' => $asset->id];

        $this->put('/admin/about', $payload)->assertRedirect('/admin/about');

        $this->assertSame($asset->path, $about->fresh()->image_path);
        $this->assertSame($about->image_alt, $about->fresh()->image_alt);
        $this->assertSame($about->active, $about->fresh()->active);
        $this->assertSame(3, $about->benefits()->count());
    }
}
