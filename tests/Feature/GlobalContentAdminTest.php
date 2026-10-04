<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GlobalContentAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_update_shared_content_and_logo_with_localized_api_output(): void
    {
        $admin = $this->cmsAdmin();
        $settings = SiteSetting::findOrFail(1);
        $payload = [...$this->contentPayload($settings), 'footer_description' => $this->bilingual('Nou peu de pàgina', 'Nuevo pie de página'),
            'callout_title' => $this->bilingual('Parlem avui', 'Hablemos hoy'), 'logo' => UploadedFile::fake()->image('logo.png')];

        $this->actingAs($admin)->put('/admin/settings', $payload)->assertRedirect('/admin/settings')->assertSessionHas('status');

        $this->assertSame(['ca' => 'Nou peu de pàgina', 'es' => 'Nuevo pie de página'], $settings->fresh()->footer_description);
        Storage::disk('public')->assertMissing('seed/logo.svg');
        Storage::disk('public')->assertExists($settings->fresh()->logo_path);
        $this->getJson('/api/v1/site?locale=es')->assertJsonPath('data.footer_description', 'Nuevo pie de página')->assertJsonPath('data.callout_title', 'Hablemos hoy')->assertJsonPath('data.contact_email', null);
    }

    public function test_shared_copy_requires_both_languages_when_submitted(): void
    {
        $admin = $this->cmsAdmin();

        $this->actingAs($admin)->put('/admin/settings', ['footer_description' => ['ca' => 'Només català']])->assertSessionHasErrors('footer_description.es');

        $this->assertSame('Arxivística, investigació i patrimoni documental.', SiteSetting::findOrFail(1)->footer_description['ca']);
    }

    public function test_missing_logo_upload_preserves_existing_file(): void
    {
        $admin = $this->cmsAdmin();

        $this->actingAs($admin)->put('/admin/settings', ['phone' => null])->assertRedirect('/admin/settings');

        $this->assertSame('seed/logo.svg', SiteSetting::findOrFail(1)->logo_path);
        Storage::disk('public')->assertExists('seed/logo.svg');
    }

    public function test_invalid_shared_destination_is_rejected(): void
    {
        $admin = $this->cmsAdmin();

        $this->actingAs($admin)->put('/admin/settings', ['callout_destination' => 'javascript:alert(1)'])->assertSessionHasErrors('callout_destination');

        $this->assertSame('/contacto', SiteSetting::findOrFail(1)->callout_destination);
    }
}
