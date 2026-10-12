<?php

namespace Tests\Feature;

use App\Models\HomePage;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\ApprovedContentSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppearanceSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_save_theme(): void
    {
        SiteSetting::factory()->create();

        $this->put('/admin/appearance', ['primary_color' => '#123456', 'background_color' => '#ABCDEF'])->assertRedirect('/admin/login');

        $this->assertDatabaseHas('site_settings', ['id' => 1, 'primary_color' => '#8F6C35', 'background_color' => '#FFFFFF']);
    }

    public function test_non_admin_cannot_save_theme(): void
    {
        SiteSetting::factory()->create();

        $this->actingAs(User::factory()->create())->put('/admin/appearance', ['primary_color' => '#123456', 'background_color' => '#ABCDEF'])->assertForbidden();

        $this->assertDatabaseHas('site_settings', ['id' => 1, 'primary_color' => '#8F6C35', 'background_color' => '#FFFFFF']);
    }

    public function test_screen_uses_persisted_values_and_real_save_form(): void
    {
        SiteSetting::factory()->create(['primary_color' => '#123456', 'background_color' => '#ABCDEF']);
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)->get('/admin/appearance')->assertSee('value="#123456"', false)->assertSee('value="#ABCDEF"', false)
            ->assertSee('name="primary_color"', false)->assertSee('name="background_color"', false)->assertSee('form="cms-editor"', false)
            ->assertSee(route('admin.appearance.update'))->assertSee('name="_token"', false)->assertSee('data-theme-preview', false)->assertSee('data-theme-reset', false);
    }

    public function test_save_normalizes_colors_and_new_requests_read_them_without_cache_clear(): void
    {
        $admin = $this->cmsAdmin();
        SiteSetting::findOrFail(1)->update(['contact_email' => 'studio@example.test']);
        $before = $this->getJson('/api/v1/site')->json('data');

        $this->actingAs($admin)->put('/admin/appearance', ['primary_color' => '#aabbcc', 'background_color' => '#f5f2eb', 'contact_email' => 'injected@example.test'])->assertRedirect('/admin/appearance')->assertSessionHas('status', 'Los colores se han guardado.');

        $this->assertDatabaseHas('site_settings', ['id' => 1, 'primary_color' => '#AABBCC', 'background_color' => '#F5F2EB', 'contact_email' => 'studio@example.test']);
        $this->get('/admin/appearance')->assertSee('value="#AABBCC"', false)->assertSee('value="#F5F2EB"', false);
        $response = $this->getJson('/api/v1/site')->assertJsonPath('data.theme', ['primary_color' => '#AABBCC', 'background_color' => '#F5F2EB']);
        $after = $response->json('data');
        unset($before['theme'], $after['theme']);
        $this->assertSame($before, $after);
        $this->put('/admin/appearance', ['primary_color' => '#112233', 'background_color' => '#445566'])->assertRedirect('/admin/appearance');
        $this->app['env'] = 'production';
        $this->getJson('/api/v1/site')->assertJsonPath('data.theme', ['primary_color' => '#112233', 'background_color' => '#445566']);
    }

    public static function invalidColors(): array
    {
        $cases = [];
        foreach (['primary_color', 'background_color'] as $field) {
            foreach (['short' => '#FFF', 'missing hash' => '8F6C35', 'long' => '#1234567', 'alpha' => '#12345678', 'non hex' => '#GGGGGG', 'css' => 'red; background:url(javascript:alert(1))', 'html' => '<script>alert(1)</script>', 'array' => ['ca' => '#FFFFFF'], 'null' => null, 'number' => 123456] as $name => $value) {
                $cases[$field.' '.$name] = [$field, $value];
            }
        }

        return $cases;
    }

    #[DataProvider('invalidColors')]
    public function test_invalid_color_rejects_whole_save_and_preserves_both_values(string $field, mixed $value): void
    {
        SiteSetting::factory()->create(['primary_color' => '#123456', 'background_color' => '#ABCDEF']);
        $admin = User::factory()->administrator()->create();
        $message = $field === 'primary_color' ? 'Indica el color principal con seis dígitos hexadecimales (#RRGGBB).' : 'Indica el fondo con seis dígitos hexadecimales (#RRGGBB).';

        $this->actingAs($admin)->followingRedirects()->from('/admin/appearance')->put('/admin/appearance', array_replace(['primary_color' => '#000000', 'background_color' => '#000000'], [$field => $value]))->assertSee($message);

        $this->assertDatabaseHas('site_settings', ['id' => 1, 'primary_color' => '#123456', 'background_color' => '#ABCDEF']);
    }

    public function test_both_colors_are_required(): void
    {
        SiteSetting::factory()->create();
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)->put('/admin/appearance', [])->assertSessionHasErrors(['primary_color', 'background_color']);

        $this->assertDatabaseHas('site_settings', ['id' => 1, 'primary_color' => '#8F6C35', 'background_color' => '#FFFFFF']);
    }

    public function test_database_defaults_upgrade_existing_records_without_touching_contact_data(): void
    {
        $migration = require database_path('migrations/2026_10_11_111753_add_theme_colors_to_site_settings_table.php');
        $migration->down();
        DB::table('site_settings')->insert(['id' => 1, 'contact_email' => 'existing@example.test']);

        $migration->up();

        $this->assertDatabaseHas('site_settings', ['id' => 1, 'contact_email' => 'existing@example.test', 'primary_color' => '#8F6C35', 'background_color' => '#FFFFFF']);
        $this->getJson('/api/v1/site')->assertJsonPath('data.theme', ['primary_color' => '#8F6C35', 'background_color' => '#FFFFFF']);
    }

    public function test_reseeding_and_legacy_settings_saves_preserve_theme_choices(): void
    {
        $admin = $this->cmsAdmin();
        SiteSetting::findOrFail(1)->update(['primary_color' => '#112233', 'background_color' => '#445566']);

        $this->seed(ApprovedContentSeeder::class);
        $this->seed(ApprovedContentSeeder::class);
        $this->actingAs($admin)->put('/admin/settings', ['contact_email' => 'confirmed@example.test', 'primary_color' => '#000000'])->assertRedirect('/admin/settings');

        $this->assertDatabaseHas('site_settings', ['id' => 1, 'primary_color' => '#112233', 'background_color' => '#445566', 'contact_email' => 'confirmed@example.test']);
    }

    public function test_default_theme_is_language_independent_and_visibility_contract_is_unchanged(): void
    {
        $admin = $this->cmsAdmin();
        HomePage::findOrFail(1)->update(['active' => false]);
        $pages = ['home' => false, 'about' => true, 'services' => true, 'portfolio' => true, 'contact' => true, 'legal_notice' => false, 'privacy' => false, 'cookies' => false];

        foreach (['ca', 'es'] as $locale) {
            $this->getJson('/api/v1/site?locale='.$locale)->assertHeader('Content-Language', $locale)->assertJsonPath('data.theme', ['primary_color' => '#8F6C35', 'background_color' => '#FFFFFF'])->assertJsonPath('data.pages', $pages)->assertJsonPath('data.contact_email', null)->assertJsonPath('data.phone', null);
        }
        $this->getJson('/api/v1/site?locale=en')->assertUnprocessable();
        $this->getJson('/api/v1/blog')->assertNotFound();
        $this->getJson('/api/v1/pages/home')->assertNotFound();
        $this->putJson('/api/v1/site', ['theme' => ['primary_color' => '#000000']])->assertMethodNotAllowed();
    }

    public function test_theme_save_requires_csrf(): void
    {
        SiteSetting::factory()->create();
        $admin = User::factory()->administrator()->create();
        $this->app['env'] = 'local';

        $this->actingAs($admin)->put('/admin/appearance', ['primary_color' => '#123456', 'background_color' => '#ABCDEF'])->assertStatus(419);

        $this->assertDatabaseHas('site_settings', ['id' => 1, 'primary_color' => '#8F6C35', 'background_color' => '#FFFFFF']);
    }
}
