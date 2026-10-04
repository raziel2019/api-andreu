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
            'service_summaries' => $home->serviceSummaries->map(fn ($summary): array => $summary->only(['slug', 'title', 'description', 'sort_order']))->all(),
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
        Storage::disk('public')->assertMissing('seed/home-hero.png');
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
        Storage::disk('public')->assertMissing('seed/home-hero.png');
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
}
