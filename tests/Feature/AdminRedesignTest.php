<?php

namespace Tests\Feature;

use App\Models\HomePage;
use App\Models\LegalPage;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\LegalPageSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminRedesignTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function shellScreens(): array
    {
        return [
            'overview' => ['/admin', 'admin.dashboard'],
            'content' => ['/admin/content', 'admin.content.index'],
            'media' => ['/admin/media', 'admin.media.index'],
            'appearance' => ['/admin/appearance', 'admin.appearance.index'],
            'identity' => ['/admin/settings', 'admin.settings.edit'],
            'editor' => ['/admin/home', 'admin.content.index'],
            'visibility' => ['/admin/pages/visibility', 'admin.content.index'],
            'legal' => ['/admin/legal/privacy', 'admin.content.index'],
        ];
    }

    #[DataProvider('shellScreens')]
    public function test_guests_cannot_access_redesigned_screens(string $path, string $activeRoute): void
    {
        $this->get($path)->assertRedirect('/admin/login');
    }

    #[DataProvider('shellScreens')]
    public function test_non_admin_users_cannot_access_redesigned_screens(string $path, string $activeRoute): void
    {
        $this->actingAs(User::factory()->create())->get($path)->assertForbidden();
    }

    #[DataProvider('shellScreens')]
    public function test_admin_screens_share_sidebar_with_current_navigation_and_two_languages(string $path, string $activeRoute): void
    {
        $admin = $this->cmsAdmin();
        $this->seed(LegalPageSeeder::class);
        config(['app.frontend_url' => 'https://portfolio.example.test']);

        $response = $this->actingAs($admin)->get($path);

        $response->assertOk()->assertSee('Una web, dos idiomas')->assertDontSee('Modo local')->assertDontSee('Blog')
            ->assertDontSee('>EN<', false)->assertSee('https://portfolio.example.test');
        $response->assertSee('href="'.route($activeRoute).'" class="nav-item is-active"', false)->assertSee('aria-current="page"', false);
        foreach (['admin.dashboard', 'admin.content.index', 'admin.media.index', 'admin.appearance.index', 'admin.settings.edit'] as $route) {
            $response->assertSee(route($route));
        }
    }

    public function test_dashboard_counts_and_visibility_reflect_database_changes(): void
    {
        $admin = $this->cmsAdmin();
        $this->seed(LegalPageSeeder::class);
        HomePage::findOrFail(1)->update(['active' => false]);
        LegalPage::where('type', 'privacy_policy')->update(['active' => true]);
        Service::firstOrFail()->delete();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertViewHas('languageCount', 2)->assertViewHas('serviceCount', 3)
            ->assertViewHas('pages', fn (array $pages): bool => count($pages) === 8 && ! $pages['home'] && $pages['privacy'] && count(array_filter($pages)) === 5)
            ->assertSee('08', false)->assertSee('02', false)->assertSee('05', false)->assertSee('Oculta en la web')->assertSee('Visible en la web');
    }

    public function test_content_entry_point_links_to_all_existing_editors_and_visibility(): void
    {
        $admin = $this->cmsAdmin();
        $this->seed(LegalPageSeeder::class);

        $response = $this->actingAs($admin)->get('/admin/content');

        foreach (['admin.home.edit', 'admin.about.edit', 'admin.services.page.edit', 'admin.portfolio.edit', 'admin.contact.edit', 'admin.pages.visibility.index'] as $route) {
            $response->assertSee(route($route));
            $this->get(route($route))->assertOk();
        }
        foreach (['legal-notice', 'privacy', 'cookies'] as $section) {
            $response->assertSee(route('admin.legal.edit', $section));
            $this->get(route('admin.legal.edit', $section))->assertOk();
        }
    }

    public static function editableScreens(): array
    {
        return array_map(fn (string $path): array => [$path], ['/admin/home', '/admin/about', '/admin/services/page', '/admin/services', '/admin/services/1/edit', '/admin/portfolio', '/admin/contact', '/admin/settings', '/admin/legal/privacy', '/admin/contact/options/1/edit']);
    }

    #[DataProvider('editableScreens')]
    public function test_global_save_targets_the_existing_screen_form(string $path): void
    {
        $admin = $this->cmsAdmin();
        $this->seed(LegalPageSeeder::class);

        $this->actingAs($admin)->get($path)->assertSee('form="cms-editor"', false)->assertSee('<form id="cms-editor" method="POST"', false)->assertSee('name="_token"', false);
    }

    public function test_legacy_settings_endpoint_does_not_accept_theme_aliases(): void
    {
        $admin = $this->cmsAdmin();
        $this->seed(LegalPageSeeder::class);
        $before = SiteSetting::findOrFail(1)->contact_email;

        $this->actingAs($admin)->put('/admin/settings', ['primary' => '#000000', 'background' => '#000000'])->assertRedirect('/admin/settings');

        $this->assertSame($before, SiteSetting::findOrFail(1)->contact_email);
        $this->getJson('/api/v1/site')->assertJsonMissingPath('data.primary')->assertJsonMissingPath('data.background');
    }

    public function test_unconfigured_frontend_has_no_fake_public_link(): void
    {
        $admin = $this->cmsAdmin();
        $this->seed(LegalPageSeeder::class);
        config(['app.frontend_url' => null]);

        $this->actingAs($admin)->get('/admin')->assertSee('Web pública pendiente de configurar.')->assertDontSee('>Ver web', false)->assertDontSee('<iframe', false);
    }

    public function test_empty_service_list_has_no_global_save_target(): void
    {
        $admin = $this->cmsAdmin();
        Service::query()->delete();

        $this->actingAs($admin)->get('/admin/services')->assertSee('Encara no hi ha serveis.')->assertDontSee('form="cms-editor"', false);
    }
}
