<?php

namespace Tests\Feature;

use App\Models\AboutPage;
use App\Models\ContactServiceOption;
use App\Models\Service;
use Database\Seeders\ApprovedContentSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicContentApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function seedContent(): void
    {
        Storage::fake('public');
        $this->seed(ApprovedContentSeeder::class);
    }

    public static function localizedEndpoints(): array
    {
        $endpoints = [
            'home' => ['/api/v1/pages/home', 'data.hero_title', 'Arxiver, Gestor Documental | Especialista en Preservació del Patrimoni Documental', 'Archivero, Gestor Documental | Especialista en Preservación del Patrimonio Documental'],
            'about' => ['/api/v1/pages/about', 'data.title', 'SOBRE MI', 'SOBRE MÍ'],
            'portfolio' => ['/api/v1/pages/portfolio', 'data.title', 'Portafoli', 'Portafolio'],
            'contact' => ['/api/v1/pages/contact', 'data.title', 'Contacte', 'Contacto'],
            'services' => ['/api/v1/services', 'data.0.title', 'Consultoria i Assessorament Especialitzat', 'Consultoría y Asesoramiento Especializado'],
            'site' => ['/api/v1/site', 'data.location', 'Catalunya · Projectes presencials i a distància', 'Cataluña · Proyectos presenciales y a distancia'],
        ];
        $cases = [];
        foreach ($endpoints as $name => [$url, $field, $ca, $es]) {
            foreach (['default', 'ca', 'es'] as $locale) {
                $cases[$name.' '.$locale] = [$url, $field, $locale, $locale === 'es' ? $es : $ca];
            }
        }

        return $cases;
    }

    #[DataProvider('localizedEndpoints')]
    public function test_public_endpoints_return_localized_content(string $url, string $field, string $locale, string $expected): void
    {
        $this->seedContent();
        $query = $locale === 'default' ? '' : '?locale='.$locale;

        $response = $this->getJson($url.$query);

        $response->assertOk()->assertHeader('Content-Language', $locale === 'es' ? 'es' : 'ca')->assertJsonPath($field, $expected);
        $this->assertIsString($response->json($field));
    }

    public function test_about_includes_all_benefits_and_numbered_working_method_steps(): void
    {
        $this->seedContent();

        $this->getJson('/api/v1/pages/about?locale=es')->assertJsonCount(3, 'data.benefits')->assertJsonCount(3, 'data.working_method_steps')
            ->assertJsonPath('data.working_method_steps.1.content', 'Plan de actuación: Definimos una hoja de ruta clara con un presupuesto cerrado, sin sorpresas.');
    }

    public function test_active_services_and_features_are_sorted_with_deterministic_ties(): void
    {
        $this->seedContent();
        Service::where('slug', 'advice')->update(['active' => false]);
        Service::where('slug', 'research')->update(['sort_order' => 0]);
        Service::whereIn('slug', ['organization', 'digitization'])->update(['sort_order' => 10]);
        $research = Service::where('slug', 'research')->firstOrFail();
        $research->features()->where('sort_order', 0)->update(['sort_order' => 20]);

        $this->getJson('/api/v1/services')->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.slug', 'research')->assertJsonPath('data.1.slug', 'organization')->assertJsonPath('data.2.slug', 'digitization')
            ->assertJsonPath('data.0.features.0.content', "Genealogia i història familiar: Disseny i confecció d'arbres genealògics a mida.")
            ->assertJsonCount(3, 'data.0.features')->assertJsonPath('meta.page.title', 'SERVEIS');
    }

    public function test_contact_returns_the_six_approved_options(): void
    {
        $this->seedContent();

        $this->getJson('/api/v1/pages/contact')->assertJsonCount(6, 'data.service_options')
            ->assertJsonPath('data.service_options.0.slug', 'organization')->assertJsonPath('data.service_options.5.label', 'Altres / Projecte a mida');
    }

    public function test_inactive_contact_options_are_excluded_and_order_is_applied(): void
    {
        $this->seedContent();
        ContactServiceOption::where('slug', 'organization')->update(['active' => false]);
        ContactServiceOption::where('slug', 'other')->update(['sort_order' => 0]);

        $this->getJson('/api/v1/pages/contact')->assertJsonCount(5, 'data.service_options')->assertJsonPath('data.service_options.0.slug', 'other');
    }

    public function test_portfolio_is_under_construction_without_projects_or_invented_linkedin_url(): void
    {
        $this->seedContent();

        $this->getJson('/api/v1/pages/portfolio')->assertJsonPath('data.under_construction', true)->assertJsonPath('data.linkedin_destination', null)->assertJsonMissingPath('data.projects');
    }

    public function test_site_does_not_invent_contact_values(): void
    {
        $this->seedContent();

        $this->getJson('/api/v1/site')->assertJsonPath('data.contact_email', null)->assertJsonPath('data.phone', null)
            ->assertJsonPath('data.linkedin_url', null)->assertJsonPath('data.instagram_url', null)->assertJsonMissingPath('data.password');
    }

    public function test_missing_secondary_translation_falls_back_to_catalan(): void
    {
        $this->seedContent();
        AboutPage::findOrFail(1)->update(['title' => ['ca' => 'Títol canònic']]);

        $this->getJson('/api/v1/pages/about?locale=es')->assertJsonPath('data.title', 'Títol canònic');
    }

    public static function invalidLocales(): array
    {
        return [['en'], ['fr'], [''], ['ca&locale[]=es']];
    }

    #[DataProvider('invalidLocales')]
    public function test_unsupported_locale_returns_422(string $locale): void
    {
        $this->getJson('/api/v1/pages/home?locale='.$locale)->assertUnprocessable()->assertJsonPath('message', 'Idioma no admès. Utilitza ca o es.');
    }

    public static function writeEndpoints(): array
    {
        $cases = [];
        foreach (['/api/v1/site', '/api/v1/pages/home', '/api/v1/pages/about', '/api/v1/pages/contact', '/api/v1/pages/portfolio', '/api/v1/services'] as $url) {
            foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
                $cases[] = [$method, $url];
            }
        }

        return $cases;
    }

    #[DataProvider('writeEndpoints')]
    public function test_public_api_rejects_writes(string $method, string $url): void
    {
        $this->json($method, $url, [])->assertMethodNotAllowed();
    }

    public function test_blog_endpoints_do_not_exist(): void
    {
        $this->getJson('/api/v1/blog')->assertNotFound();
        $this->getJson('/api/v1/pages/blog')->assertNotFound();
        $this->get('/admin/blog')->assertNotFound();
    }

    public function test_configured_frontend_origin_can_read_the_api_without_credentials(): void
    {
        $this->seedContent();
        config(['cors.allowed_origins' => ['https://frontend.example.test']]);

        $this->withHeader('Origin', 'https://frontend.example.test')->getJson('/api/v1/site')
            ->assertHeader('Access-Control-Allow-Origin', 'https://frontend.example.test')->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    public function test_unapproved_frontend_origin_does_not_receive_a_matching_cors_header(): void
    {
        $this->seedContent();
        config(['cors.allowed_origins' => ['https://frontend.example.test']]);

        $this->withHeader('Origin', 'https://unapproved.example.test')->getJson('/api/v1/site')->assertHeader('Access-Control-Allow-Origin', 'https://frontend.example.test');
    }
}
