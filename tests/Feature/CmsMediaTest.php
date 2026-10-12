<?php

namespace Tests\Feature;

use App\Models\AboutPage;
use App\Models\Service;
use App\Models\ServiceFeature;
use App\Models\ServicePage;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CmsMediaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function uploadEndpoints(): array
    {
        return [['/admin/about', 'image'], ['/admin/services/1', 'icon'], ['/admin/services/page', 'image'], ['/admin/settings', 'logo']];
    }

    private function payload(string $path): array
    {
        return match ($path) {
            '/admin/about' => [...$this->contentPayload(AboutPage::findOrFail(1)), 'benefits' => [], 'working_method_steps' => []],
            '/admin/services/1' => [...$this->contentPayload(Service::findOrFail(1)), 'features' => []],
            '/admin/services/page' => $this->contentPayload(ServicePage::findOrFail(1)),
            '/admin/settings' => [],
        };
    }

    #[DataProvider('uploadEndpoints')]
    public function test_every_new_upload_endpoint_rejects_active_svg(string $path, string $field): void
    {
        $admin = $this->cmsAdmin();
        $payload = [...$this->payload($path), $field => UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')];

        $this->actingAs($admin)->put($path, $payload)->assertSessionHasErrors($field);

        $this->assertSame([], Storage::disk('public')->files('cms'));
    }

    #[DataProvider('uploadEndpoints')]
    public function test_every_upload_endpoint_preserves_shared_files_on_replacement(string $path, string $field): void
    {
        $admin = $this->cmsAdmin();
        if ($path === '/admin/about') {
            AboutPage::findOrFail(1)->update(['image_path' => 'seed/home-hero.png']);
        }
        if ($path === '/admin/services/1') {
            Service::findOrFail(1)->update(['icon_path' => 'seed/summary-organization.svg']);
        }
        if ($path === '/admin/settings') {
            SiteSetting::findOrFail(1)->update(['logo_path' => 'seed/home-hero.png']);
        }
        $old = match ($path) {
            '/admin/about', '/admin/settings' => 'seed/home-hero.png',
            '/admin/services/1' => 'seed/summary-organization.svg',
            '/admin/services/page' => 'seed/documents.png',
        };
        $payload = [...$this->payload($path), $field => UploadedFile::fake()->image('replacement.png')];

        $this->actingAs($admin)->put($path, $payload)->assertRedirect();

        Storage::disk('public')->assertExists($old);
        $this->assertCount(1, Storage::disk('public')->files('cms'));
    }

    public function test_library_media_is_retained_after_confirmed_service_deletion(): void
    {
        $admin = $this->cmsAdmin();
        $service = Service::findOrFail(1);
        $payload = [...$this->payload('/admin/services/1'), 'icon' => UploadedFile::fake()->image('icon.png')];
        $this->actingAs($admin)->put('/admin/services/1', $payload)->assertRedirect();
        $path = $service->fresh()->icon_path;

        $this->delete('/admin/services/1', ['confirm_delete' => 1])->assertRedirect('/admin/services');

        Storage::disk('public')->assertExists($path);
    }

    public function test_failed_content_transaction_rolls_back_and_removes_new_upload(): void
    {
        $admin = $this->cmsAdmin();
        $payload = [...$this->payload('/admin/services/1'), 'title' => $this->bilingual('No desat', 'No guardado'), 'icon' => UploadedFile::fake()->image('new.png'),
            'features' => [['content' => $this->bilingual(), 'sort_order' => 0]]];
        ServiceFeature::creating(function (): void {
            throw new RuntimeException('Simulated persistence failure');
        });
        try {
            $this->actingAs($admin)->put('/admin/services/1', $payload)->assertStatus(500);
        } finally {
            ServiceFeature::flushEventListeners();
        }

        $this->assertSame('Consultoria i Assessorament Especialitzat', Service::findOrFail(1)->title['ca']);
        $this->assertSame(3, Service::findOrFail(1)->features()->count());
        Storage::disk('public')->assertExists('seed/service-advice.svg');
        $this->assertSame([], Storage::disk('public')->files('cms'));
    }

    public function test_media_removal_does_not_accept_an_invalid_boolean(): void
    {
        $admin = $this->cmsAdmin();

        $this->actingAs($admin)->put('/admin/settings', ['remove_logo' => 'yes'])->assertSessionHasErrors('remove_logo');

        Storage::disk('public')->assertExists('seed/logo.svg');
    }

    public function test_logo_upload_with_disguised_extension_is_rejected(): void
    {
        $admin = $this->cmsAdmin();

        $this->actingAs($admin)->put('/admin/settings', ['logo' => UploadedFile::fake()->image('logo.php')])->assertSessionHasErrors('logo');

        $this->assertSame([], Storage::disk('public')->files('cms'));
    }

    public function test_service_icon_larger_than_upload_limit_is_rejected(): void
    {
        $admin = $this->cmsAdmin();
        $payload = [...$this->payload('/admin/services/1'), 'icon' => UploadedFile::fake()->image('large.jpg')->size(5121)];

        $this->actingAs($admin)->put('/admin/services/1', $payload)->assertSessionHasErrors('icon');

        Storage::disk('public')->assertExists('seed/service-advice.svg');
    }
}
