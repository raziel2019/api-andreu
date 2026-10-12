<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WhatsAppSettingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_default_number_and_url_are_exposed_in_both_locales(): void
    {
        $settings = SiteSetting::factory()->create();

        $this->assertSame('+34694229352', $settings->fresh()->whatsapp_number);
        foreach (['ca', 'es'] as $locale) {
            $this->getJson('/api/v1/site?locale='.$locale)->assertOk()
                ->assertJsonPath('data.whatsapp_number', '+34694229352')
                ->assertJsonPath('data.whatsapp_url', 'https://wa.me/34694229352');
        }
    }

    public static function validNumbers(): array
    {
        return [
            'formatted' => ['+34 694 229 352', '+34694229352'],
            'international prefix' => ['0034 (694) 229-352', '+34694229352'],
            'digits' => ['34694229352', '+34694229352'],
            'different country' => ['+52 (55) 1234-5678', '+525512345678'],
            'maximum length' => ['+123456789012345', '+123456789012345'],
            'empty' => ['', null],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_admin_persists_normalized_number_and_derived_url(string $input, ?string $expected): void
    {
        $admin = $this->cmsAdmin();
        $settings = SiteSetting::findOrFail(1);
        $settings->update(['phone' => 'Telèfon existent', 'contact_email' => 'contact@example.test']);

        $this->actingAs($admin)->put('/admin/settings', ['whatsapp_number' => $input])
            ->assertSessionHasNoErrors()->assertRedirect('/admin/settings');

        $this->assertSame($expected, $settings->fresh()->whatsapp_number);
        $this->assertSame('Telèfon existent', $settings->fresh()->phone);
        $this->assertSame('contact@example.test', $settings->fresh()->contact_email);
        $this->getJson('/api/v1/site')->assertJsonPath('data.whatsapp_url', $expected === null ? null : 'https://wa.me/'.substr($expected, 1));
    }

    public static function invalidNumbers(): array
    {
        return [
            ['javascript:alert(1)'], ['https://wa.me/34694229352'], ['+34 694 229 352 ext 1'],
            ['+012345678'], ['+1234567'], ['+1234567890123456'], ['++34694229352'],
            ["+34694\n229352"], [['ca' => '+34694229352']], ['+34/694/229/352'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_invalid_number_does_not_change_settings(mixed $number): void
    {
        $admin = $this->cmsAdmin();

        $this->actingAs($admin)->put('/admin/settings', ['whatsapp_number' => $number])->assertSessionHasErrors('whatsapp_number');

        $this->assertDatabaseHas('site_settings', ['id' => 1, 'whatsapp_number' => '+34694229352']);
    }

    public function test_omitted_whatsapp_field_preserves_saved_number(): void
    {
        $admin = $this->cmsAdmin();
        SiteSetting::findOrFail(1)->update(['whatsapp_number' => '+525512345678']);

        $this->actingAs($admin)->put('/admin/settings', ['contact_email' => 'updated@example.test'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_settings', ['id' => 1, 'whatsapp_number' => '+525512345678', 'contact_email' => 'updated@example.test']);
        $this->get('/admin/settings')->assertSee('whatsapp_number')->assertSee('+525512345678');
    }

    public function test_non_admin_cannot_change_whatsapp(): void
    {
        SiteSetting::factory()->create(['id' => 1]);

        $this->actingAs(User::factory()->create())->put('/admin/settings', ['whatsapp_number' => '+525512345678'])->assertForbidden();

        $this->assertDatabaseHas('site_settings', ['id' => 1, 'whatsapp_number' => '+34694229352']);
    }

    public function test_migration_initializes_default_and_preserves_existing_content(): void
    {
        $settings = SiteSetting::factory()->create(['phone' => 'Original', 'primary_color' => '#123456']);
        $migration = require database_path('migrations/2026_10_12_133107_add_whatsapp_number_to_site_settings_table.php');
        $migration->down();

        $migration->up();

        $this->assertDatabaseHas('site_settings', ['id' => $settings->id, 'phone' => 'Original', 'primary_color' => '#123456', 'whatsapp_number' => '+34694229352']);
    }
}
