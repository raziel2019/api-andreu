<?php

namespace Tests\Feature;

use App\Models\AboutPage;
use App\Models\ContactPage;
use App\Models\HomePage;
use App\Models\LegalPage;
use App\Models\PortfolioPage;
use App\Models\ServicePage;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\ApprovedContentSeeder;
use Database\Seeders\LegalPageSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PageVisibilityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function pages(): array
    {
        return [
            'home' => ['home', HomePage::class, '/api/v1/pages/home', []],
            'about' => ['about', AboutPage::class, '/api/v1/pages/about', []],
            'services' => ['services', ServicePage::class, '/api/v1/services', []],
            'portfolio' => ['portfolio', PortfolioPage::class, '/api/v1/pages/portfolio', []],
            'contact' => ['contact', ContactPage::class, '/api/v1/pages/contact', []],
            'legal notice' => ['legal_notice', LegalPage::class, '/api/v1/legal/legal-notice', ['type' => 'legal_notice']],
            'privacy' => ['privacy', LegalPage::class, '/api/v1/legal/privacy', ['type' => 'privacy_policy']],
            'cookies' => ['cookies', LegalPage::class, '/api/v1/legal/cookies', ['type' => 'cookie_policy']],
        ];
    }

    #[DataProvider('pages')]
    public function test_admin_hides_and_restores_each_page_without_changing_content(string $key, string $model, string $endpoint, array $attributes): void
    {
        $page = $model::factory()->create([...$attributes, 'active' => true]);
        SiteSetting::factory()->create();
        $original = $page->fresh()->attributesToArray();
        unset($original['active'], $original['updated_at']);
        $published = $this->getJson($endpoint)->assertOk()->json();
        $this->actingAs(User::factory()->administrator()->create());

        $this->put('/admin/pages/visibility/'.$key, ['active' => false, 'title' => ['ca' => 'Injected']])->assertRedirect('/admin/pages/visibility')->assertSessionHasNoErrors();
        $this->assertFalse($page->fresh()->active);
        $this->getJson($endpoint)->assertNotFound()->assertJsonMissingPath('data')->assertJsonMissingPath('meta.page');
        $this->getJson('/api/v1/site')->assertJsonPath('data.pages.'.$key, false);
        $hidden = $page->fresh()->attributesToArray();
        unset($hidden['active'], $hidden['updated_at']);
        $this->assertSame($original, $hidden);

        $this->put('/admin/pages/visibility/'.$key, ['active' => true])->assertSessionHasNoErrors();
        $this->assertTrue($page->fresh()->active);
        $this->getJson($endpoint)->assertOk()->assertExactJson($published);
        $this->getJson('/api/v1/site')->assertJsonPath('data.pages.'.$key, true);
    }

    public function test_guests_and_non_admins_cannot_manage_visibility(): void
    {
        $page = HomePage::factory()->create();
        $this->get('/admin/pages/visibility')->assertRedirect('/admin/login');
        $this->put('/admin/pages/visibility/home', ['active' => false])->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create());
        $this->get('/admin/pages/visibility')->assertForbidden();
        $this->put('/admin/pages/visibility/home', ['active' => false])->assertForbidden();
        $this->assertTrue($page->fresh()->active);
    }

    public function test_admin_can_access_all_eight_visibility_controls(): void
    {
        $this->actingAs(User::factory()->administrator()->create())->get('/admin/pages/visibility')
            ->assertOk()->assertSee('Visibilitat de pàgines')->assertSee('Home')->assertSee('Sobre mi')
            ->assertSee('Serveis')->assertSee('Portafoli')->assertSee('Contacte')->assertSee('Avís legal')
            ->assertSee('Privacitat')->assertSee('Cookies')->assertDontSee('Blog')->assertDontSee('Terms');
    }

    public function test_site_reports_seed_defaults_and_preserves_visibility_on_reseeding(): void
    {
        Storage::fake('public');
        $this->seed([ApprovedContentSeeder::class, LegalPageSeeder::class]);
        $expected = ['home' => true, 'about' => true, 'services' => true, 'portfolio' => true, 'contact' => true, 'legal_notice' => false, 'privacy' => false, 'cookies' => false];
        $this->getJson('/api/v1/site')->assertOk()->assertJsonPath('data.pages', $expected);
        foreach ([HomePage::class, AboutPage::class, ServicePage::class, PortfolioPage::class, ContactPage::class] as $model) {
            $model::findOrFail(1)->update(['active' => false]);
        }
        $legal = LegalPage::where('type', 'legal_notice')->firstOrFail();
        $legal->update(['body' => $this->bilingual(), 'active' => true]);

        $this->seed([ApprovedContentSeeder::class, LegalPageSeeder::class]);

        $this->getJson('/api/v1/site?locale=es')->assertJsonPath('data.pages', ['home' => false, 'about' => false, 'services' => false, 'portfolio' => false, 'contact' => false, 'legal_notice' => true, 'privacy' => false, 'cookies' => false]);
    }

    public function test_missing_pages_are_hidden_and_cannot_be_created_by_visibility_updates(): void
    {
        SiteSetting::factory()->create();
        $this->getJson('/api/v1/site')->assertJsonPath('data.pages', ['home' => false, 'about' => false, 'services' => false, 'portfolio' => false, 'contact' => false, 'legal_notice' => false, 'privacy' => false, 'cookies' => false]);
        $this->actingAs(User::factory()->administrator()->create())->put('/admin/pages/visibility/home', ['active' => true])->assertNotFound();
        $this->assertDatabaseCount('home_pages', 0);
    }

    public function test_visibility_requires_a_boolean_and_rejects_unknown_pages(): void
    {
        $page = HomePage::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());
        $this->putJson('/admin/pages/visibility/home', [])->assertUnprocessable()->assertJsonValidationErrors('active');
        $this->putJson('/admin/pages/visibility/home', ['active' => 'yes'])->assertUnprocessable()->assertJsonValidationErrors('active');
        foreach (['blog', 'terms', 'unknown'] as $key) {
            $this->put('/admin/pages/visibility/'.$key, ['active' => true])->assertNotFound();
        }
        $this->assertTrue($page->fresh()->active);
        $this->putJson('/api/v1/pages/home', ['active' => false])->assertMethodNotAllowed();
        $this->getJson('/api/v1/blog')->assertNotFound();
        $this->getJson('/api/v1/legal/terms')->assertNotFound();
    }

    public function test_legal_visibility_cannot_bypass_publication_validation(): void
    {
        $page = LegalPage::factory()->create(['body' => ['ca' => 'Text', 'es' => null]]);
        $this->actingAs(User::factory()->administrator()->create());
        $this->putJson('/admin/pages/visibility/legal_notice', ['active' => true, 'body' => $this->bilingual()])->assertUnprocessable()->assertJsonValidationErrors('body.es');
        $this->assertFalse($page->fresh()->active);
        $this->assertNull($page->fresh()->body['es']);
    }

    public function test_child_content_survives_hiding_and_restoring_pages(): void
    {
        $admin = $this->cmsAdmin();
        $original = [];
        foreach (['/api/v1/pages/home', '/api/v1/pages/about', '/api/v1/services', '/api/v1/pages/contact'] as $endpoint) {
            $original[$endpoint] = $this->getJson($endpoint)->assertOk()->json();
        }
        $this->actingAs($admin);
        foreach (['home', 'about', 'services', 'contact'] as $key) {
            $this->put('/admin/pages/visibility/'.$key, ['active' => false])->assertSessionHasNoErrors();
            $this->put('/admin/pages/visibility/'.$key, ['active' => true])->assertSessionHasNoErrors();
        }
        foreach ($original as $endpoint => $content) {
            $this->getJson($endpoint)->assertExactJson($content);
        }
    }
}
