<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SiteSettingAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrator_can_save_confirmed_contact_settings(): void
    {
        $settings = SiteSetting::factory()->create();
        $user = User::factory()->administrator()->create();

        $this->actingAs($user)->put('/admin/settings', [
            'contact_email' => 'client@example.test', 'phone' => null,
            'location' => ['ca' => 'Barcelona', 'es' => 'Barcelona'],
            'linkedin_url' => 'https://www.linkedin.com/in/example', 'instagram_url' => null,
            'logo_path' => 'injected.php',
        ])->assertRedirect('/admin/settings');

        $this->assertDatabaseHas('site_settings', ['id' => $settings->id, 'contact_email' => 'client@example.test', 'logo_path' => null]);
        $this->getJson('/api/v1/site')->assertJsonPath('data.contact_email', 'client@example.test');
    }

    public static function invalidSettings(): array
    {
        return [['contact_email', 'incorrecte'], ['linkedin_url', 'javascript:alert(1)'], ['instagram_url', 'ftp://example.test']];
    }

    #[DataProvider('invalidSettings')]
    public function test_invalid_contact_settings_are_rejected(string $field, string $value): void
    {
        $settings = SiteSetting::factory()->create();
        $user = User::factory()->administrator()->create();

        $this->actingAs($user)->put('/admin/settings', [$field => $value])->assertSessionHasErrors($field);

        $this->assertNull($settings->fresh()->getAttribute($field));
    }

    public function test_guest_cannot_update_settings(): void
    {
        $settings = SiteSetting::factory()->create();

        $this->put('/admin/settings', ['contact_email' => 'attacker@example.test'])->assertRedirect('/admin/login');

        $this->assertNull($settings->fresh()->contact_email);
    }
}
