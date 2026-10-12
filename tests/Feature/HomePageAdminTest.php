<?php

namespace Tests\Feature;

use App\Models\HomePage;
use App\Models\User;
use Database\Seeders\ApprovedContentSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HomePageAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function seedHome(): HomePage
    {
        Storage::fake('public');
        $this->seed(ApprovedContentSeeder::class);

        return HomePage::with('serviceSummaries')->findOrFail(1);
    }

    private function payload(HomePage $home): array
    {
        return [
            ...$home->only(['hero_title', 'hero_content', 'hero_eyebrow', 'hero_image_alt', 'hero_image_caption', 'hero_cta_label', 'services_heading', 'services_introduction', 'stories_heading', 'stories_introduction', 'portfolio_title', 'portfolio_image_alt', 'portfolio_cta_label', 'cta_title', 'cta_content', 'cta_label', 'hero_cta_destination', 'portfolio_cta_destination', 'cta_destination']),
            'service_summaries' => $home->serviceSummaries->map(fn ($summary): array => $summary->only(['slug', 'title', 'description', 'sort_order', 'icon']))->all(),
        ];
    }

    public function test_an_administrator_can_edit_home_and_public_api_reflects_it(): void
    {
        $home = $this->seedHome();
        $user = User::factory()->administrator()->create();
        $payload = $this->payload($home);
        $payload['hero_title'] = ['ca' => 'Nou títol', 'es' => 'Nuevo título'];
        $payload['hero_image_path'] = 'untrusted.php';

        $this->actingAs($user)->put('/admin/home', $payload)->assertRedirect('/admin/home')->assertSessionHas('status');

        $this->assertSame(['ca' => 'Nou títol', 'es' => 'Nuevo título'], $home->fresh()->hero_title);
        $this->assertSame('seed/home-hero.png', $home->fresh()->hero_image_path);
        $this->getJson('/api/v1/pages/home')->assertJsonPath('data.hero_title', 'Nou títol');
        $this->getJson('/api/v1/pages/home?locale=es')->assertJsonPath('data.hero_title', 'Nuevo título');
    }

    public function test_home_form_shows_separate_languages_and_escapes_content(): void
    {
        $home = $this->seedHome();
        $home->update(['hero_title' => ['ca' => '<script>alert(1)</script>', 'es' => 'Títol']]);
        $user = User::factory()->administrator()->create();

        $this->actingAs($user)->get('/admin/home')->assertOk()->assertSee('Català')->assertSee('Castellano')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_guests_cannot_update_home(): void
    {
        $home = $this->seedHome();

        $this->put('/admin/home', ['hero_title' => ['ca' => 'No', 'es' => 'No']])->assertRedirect('/admin/login');

        $this->assertSame($home->hero_title, $home->fresh()->hero_title);
    }

    public function test_image_upload_replaces_the_reference_and_deletes_previous_file(): void
    {
        $home = $this->seedHome();
        $user = User::factory()->administrator()->create();
        $payload = $this->payload($home);
        $payload['hero_image'] = UploadedFile::fake()->image('hero.jpg', 800, 600);

        $this->actingAs($user)->put('/admin/home', $payload)->assertRedirect('/admin/home');

        $path = $home->fresh()->hero_image_path;
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertExists('seed/home-hero.png');
        $this->getJson('/api/v1/pages/home')->assertJsonPath('data.hero_image_url', Storage::disk('public')->url($path));
    }

    public function test_replacing_portfolio_image_preserves_the_shared_services_image(): void
    {
        $home = $this->seedHome();
        $user = User::factory()->administrator()->create();
        $payload = [...$this->payload($home), 'portfolio_image' => UploadedFile::fake()->image('portfolio.jpg')];

        $this->actingAs($user)->put('/admin/home', $payload)->assertRedirect('/admin/home');

        Storage::disk('public')->assertExists('seed/documents.png');
        Storage::disk('public')->assertExists($home->fresh()->portfolio_image_path);
        $this->getJson('/api/v1/services')->assertJsonPath('meta.page.image_url', Storage::disk('public')->url('seed/documents.png'));
    }

    public function test_image_can_be_removed(): void
    {
        $home = $this->seedHome();
        $user = User::factory()->administrator()->create();
        $payload = [...$this->payload($home), 'remove_hero_image' => '1'];

        $this->actingAs($user)->put('/admin/home', $payload)->assertRedirect('/admin/home');

        $this->assertNull($home->fresh()->hero_image_path);
        Storage::disk('public')->assertExists('seed/home-hero.png');
    }

    public static function invalidImages(): array
    {
        return ['document' => ['document'], 'oversized' => ['oversized'], 'svg' => ['svg'], 'dimensions' => ['dimensions'], 'wrong-extension' => ['wrong-extension']];
    }

    #[DataProvider('invalidImages')]
    public function test_invalid_media_is_rejected_without_changing_content(string $kind): void
    {
        $home = $this->seedHome();
        $user = User::factory()->administrator()->create();
        $file = match ($kind) {
            'document' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
            'oversized' => UploadedFile::fake()->image('large.jpg')->size(5121),
            'svg' => UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'dimensions' => UploadedFile::fake()->image('wide.jpg', 6001, 1),
            'wrong-extension' => UploadedFile::fake()->image('image.php'),
        };
        $payload = [...$this->payload($home), 'hero_image' => $file, 'hero_title' => ['ca' => 'Canvi no vàlid', 'es' => 'Cambio no válido']];

        $this->actingAs($user)->put('/admin/home', $payload)->assertSessionHasErrors('hero_image');

        $this->assertSame($home->hero_title, $home->fresh()->hero_title);
        $this->assertSame('seed/home-hero.png', $home->fresh()->hero_image_path);
        $this->assertSame([], Storage::disk('public')->files('home'));
    }

    public static function invalidFields(): array
    {
        return [
            'missing Spanish' => ['hero_title', ['ca' => 'Títol'], 'hero_title.es'],
            'extra language' => ['hero_title', ['ca' => 'Títol', 'es' => 'Título', 'en' => 'No'], 'hero_title'],
            'script destination' => ['cta_destination', 'javascript:alert(1)', 'cta_destination'],
            'protocol relative' => ['cta_destination', '//evil.example', 'cta_destination'],
            'unsafe slash' => ['cta_destination', '/\\evil.example', 'cta_destination'],
            'invalid boolean' => ['remove_hero_image', 'yes', 'remove_hero_image'],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_home_fields_are_rejected(string $field, mixed $value, string $error): void
    {
        $home = $this->seedHome();
        $user = User::factory()->administrator()->create();
        $payload = [...$this->payload($home), $field => $value];

        $this->actingAs($user)->put('/admin/home', $payload)->assertSessionHasErrors($error);

        $this->assertSame($home->hero_title, $home->fresh()->hero_title);
    }

    public function test_summaries_can_be_added_updated_removed_and_ordered(): void
    {
        $home = $this->seedHome();
        $user = User::factory()->administrator()->create();
        $payload = $this->payload($home);
        $payload['service_summaries'] = [
            ['slug' => 'organization', 'title' => ['ca' => 'Arxius', 'es' => 'Archivos'], 'description' => ['ca' => 'Contingut', 'es' => 'Contenido'], 'sort_order' => 5],
            ['slug' => 'nou', 'title' => ['ca' => 'Nou', 'es' => 'Nuevo'], 'description' => ['ca' => 'Contingut nou', 'es' => 'Contenido nuevo'], 'sort_order' => 1],
        ];

        $this->actingAs($user)->put('/admin/home', $payload)->assertRedirect('/admin/home');

        $this->assertDatabaseCount('home_service_summaries', 2);
        $this->assertDatabaseMissing('home_service_summaries', ['slug' => 'research']);
        $this->getJson('/api/v1/pages/home')->assertJsonPath('data.service_summaries.0.slug', 'nou')->assertJsonPath('data.service_summaries.1.title', 'Arxius');
    }

    public function test_all_summaries_can_be_removed_using_the_browser_form(): void
    {
        $home = $this->seedHome();
        $user = User::factory()->administrator()->create();
        $payload = [...$this->payload($home), 'service_summaries' => ''];

        $this->actingAs($user)->put('/admin/home', $payload)->assertRedirect('/admin/home');

        $this->assertDatabaseCount('home_service_summaries', 0);
    }

    public function test_negative_ordering_is_rejected(): void
    {
        $home = $this->seedHome();
        $user = User::factory()->administrator()->create();
        $payload = $this->payload($home);
        $payload['service_summaries'][0]['sort_order'] = -1;

        $this->actingAs($user)->put('/admin/home', $payload)->assertSessionHasErrors('service_summaries.0.sort_order');

        $this->assertDatabaseHas('home_service_summaries', ['slug' => 'organization', 'sort_order' => 0]);
    }

    public static function supportedIcons(): array
    {
        return [
            'archive' => ['archive', 'seed/summary-organization.svg'],
            'search' => ['search', 'seed/summary-research.svg'],
            'scan' => ['scan', 'seed/summary-digitization.svg'],
            'book-open' => ['book-open', 'seed/service-advice.svg'],
        ];
    }

    #[DataProvider('supportedIcons')]
    public function test_icon_edits_persist_and_preserve_home_content_in_both_locales(string $icon, string $path): void
    {
        $home = $this->seedHome();
        $beforeHome = $home->getAttributes();
        $beforeSummaries = $home->serviceSummaries->map(fn ($summary): array => $summary->only(['id', 'slug', 'title', 'description', 'sort_order']))->all();
        $payload = $this->payload($home);
        $payload['service_summaries'][0]['icon'] = $icon;
        Storage::disk('public')->delete($path);

        $this->actingAs(User::factory()->administrator()->create())->put('/admin/home', $payload)
            ->assertRedirect('/admin/home')->assertSessionHasNoErrors();

        $freshHome = $home->fresh('serviceSummaries');
        $this->assertSame($beforeHome, $freshHome->getAttributes());
        $this->assertSame($beforeSummaries, $freshHome->serviceSummaries->map(fn ($summary): array => $summary->only(['id', 'slug', 'title', 'description', 'sort_order']))->all());
        $this->assertDatabaseHas('home_service_summaries', ['id' => $home->serviceSummaries[0]->id, 'icon' => $icon, 'icon_path' => $path]);
        Storage::disk('public')->assertExists($path);
        foreach (['ca', 'es'] as $locale) {
            $this->getJson('/api/v1/pages/home?locale='.$locale)->assertOk()->assertHeader('Content-Language', $locale)
                ->assertJsonPath('data.service_summaries.0.icon', $icon)
                ->assertJsonPath('data.service_summaries.0.icon_url', Storage::disk('public')->url($path))
                ->assertJsonPath('data.service_summaries.0.title', $home->serviceSummaries[0]->title[$locale])
                ->assertJsonPath('data.service_summaries.0.description', $home->serviceSummaries[0]->description[$locale])
                ->assertJsonPath('data.service_summaries.0.sort_order', 0)
                ->assertJsonPath('data.service_summaries.1.icon', 'scan');
        }
    }

    public static function invalidIcons(): array
    {
        return [
            'unknown identifier' => ['unknown'],
            'svg' => ['<svg onload="alert(1)"></svg>'],
            'javascript' => ['javascript:alert(1)'],
            'path traversal' => ['../archive'],
            'translated identifier' => ['arxiu'],
            'array' => [['archive']],
        ];
    }

    #[DataProvider('invalidIcons')]
    public function test_invalid_icon_is_rejected_without_saving_any_home_changes(mixed $icon): void
    {
        $home = $this->seedHome();
        $payload = $this->payload($home);
        $payload['hero_title'] = ['ca' => 'Canvi', 'es' => 'Cambio'];
        $payload['service_summaries'][0]['icon'] = $icon;
        $before = $home->serviceSummaries->toArray();

        $this->actingAs(User::factory()->administrator()->create())->put('/admin/home', $payload)
            ->assertSessionHasErrors('service_summaries.0.icon');

        $this->assertSame($home->hero_title, $home->fresh()->hero_title);
        $this->assertSame($before, $home->fresh('serviceSummaries')->serviceSummaries->toArray());
    }

    public function test_non_administrator_cannot_edit_summary_icons(): void
    {
        $home = $this->seedHome();
        $payload = $this->payload($home);
        $payload['service_summaries'][0]['icon'] = 'book-open';

        $this->actingAs(User::factory()->create())->put('/admin/home', $payload)->assertForbidden();

        $this->assertDatabaseHas('home_service_summaries', ['slug' => 'organization', 'icon' => 'archive']);
    }

    public function test_form_shows_current_icon_preview_and_selection_inside_each_summary(): void
    {
        $home = $this->seedHome();
        $home->serviceSummaries[0]->update(['icon' => 'book-open']);

        $this->actingAs(User::factory()->administrator()->create())->get('/admin/home')
            ->assertSee('name="service_summaries[0][icon]"', false)
            ->assertSee('value="book-open" selected', false)
            ->assertSee('data-summary-icon-preview="book-open"', false)
            ->assertSee('src="data:image/svg+xml;base64,', false)
            ->assertSee('name="service_summaries[0][sort_order]"', false);
    }

    public function test_icon_edits_require_csrf(): void
    {
        $home = $this->seedHome();
        $payload = $this->payload($home);
        $payload['service_summaries'][0]['icon'] = 'search';
        $this->app['env'] = 'local';

        $this->actingAs(User::factory()->administrator()->create())->put('/admin/home', $payload)->assertStatus(419);

        $this->assertDatabaseHas('home_service_summaries', ['slug' => 'organization', 'icon' => 'archive']);
    }

    public function test_legacy_icon_path_is_preserved_and_cannot_be_submitted_as_an_icon(): void
    {
        $home = $this->seedHome();
        $summary = $home->serviceSummaries[0];
        $summary->update(['icon' => null, 'icon_path' => 'cms/existing.png']);
        $payload = $this->payload($home->fresh('serviceSummaries'));

        $this->actingAs(User::factory()->administrator()->create())->put('/admin/home', $payload)->assertSessionHasNoErrors();

        $this->getJson('/api/v1/pages/home')->assertJsonPath('data.service_summaries.0.icon', null)
            ->assertJsonPath('data.service_summaries.0.icon_url', Storage::disk('public')->url('cms/existing.png'));
        $payload['service_summaries'][0]['icon_path'] = 'evil.svg';
        $this->put('/admin/home', $payload)->assertSessionHasErrors('service_summaries.0');
        $this->assertSame('cms/existing.png', $summary->fresh()->icon_path);
    }

    public function test_icon_edits_save_with_reordering_and_new_summary_creation(): void
    {
        $home = $this->seedHome();
        $payload = $this->payload($home);
        $payload['service_summaries'][0]['sort_order'] = 10;
        $payload['service_summaries'][0]['icon'] = 'book-open';
        $payload['service_summaries'][] = ['slug' => 'nou', 'title' => ['ca' => 'Nou', 'es' => 'Nuevo'], 'description' => ['ca' => 'Text', 'es' => 'Texto'], 'sort_order' => 0, 'icon' => 'search'];

        $this->actingAs(User::factory()->administrator()->create())->put('/admin/home', $payload)->assertSessionHasNoErrors();

        $this->getJson('/api/v1/pages/home')->assertJsonPath('data.service_summaries.0.slug', 'nou')
            ->assertJsonPath('data.service_summaries.0.icon', 'search')
            ->assertJsonPath('data.service_summaries.4.slug', 'organization')
            ->assertJsonPath('data.service_summaries.4.icon', 'book-open');
    }
}
