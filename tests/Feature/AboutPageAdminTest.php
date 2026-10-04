<?php

namespace Tests\Feature;

use App\Models\AboutBenefit;
use App\Models\AboutPage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AboutPageAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function payload(): array
    {
        $page = AboutPage::with(['benefits', 'workingMethodSteps'])->findOrFail(1);

        return [...$this->contentPayload($page),
            'benefits' => $page->benefits->map(fn ($item) => $item->only(['id', 'content', 'sort_order']))->all(),
            'working_method_steps' => $page->workingMethodSteps->map(fn ($item) => $item->only(['id', 'content', 'sort_order']))->all(),
        ];
    }

    public function test_admin_can_update_about_with_translations_and_preserve_image(): void
    {
        $admin = $this->cmsAdmin();
        $payload = [...$this->payload(), 'title' => $this->bilingual('Sobre Andreu', 'Sobre Andreu'), 'introduction' => $this->bilingual('Presentació nova', 'Presentación nueva')];

        $this->actingAs($admin)->put('/admin/about', $payload)->assertRedirect('/admin/about')->assertSessionHas('status');

        $this->assertSame(['ca' => 'Presentació nova', 'es' => 'Presentación nueva'], AboutPage::findOrFail(1)->introduction);
        Storage::disk('public')->assertExists('seed/about.png');
        $this->getJson('/api/v1/pages/about?locale=es')->assertJsonPath('data.introduction', 'Presentación nueva');
    }

    #[DataProvider('collections')]
    public function test_repeatable_items_can_be_added_updated_removed_and_reordered(string $input, string $relation, string $api): void
    {
        $admin = $this->cmsAdmin();
        $page = AboutPage::findOrFail(1);
        $existing = $page->$relation()->firstOrFail();
        $payload = $this->payload();
        $payload[$input] = [
            ['id' => $existing->id, 'content' => $this->bilingual('Element actualitzat', 'Elemento actualizado'), 'sort_order' => 8],
            ['content' => $this->bilingual('Element nou', 'Elemento nuevo'), 'sort_order' => 1],
        ];

        $this->actingAs($admin)->put('/admin/about', $payload)->assertRedirect('/admin/about');

        $this->assertSame(2, $page->$relation()->count());
        $this->assertSame('Element actualitzat', $existing->fresh()->content['ca']);
        $this->getJson('/api/v1/pages/about?locale=es')->assertJsonPath('data.'.$api.'.0.content', 'Elemento nuevo')->assertJsonPath('data.'.$api.'.1.id', $existing->id);
    }

    public static function collections(): array
    {
        return [['benefits', 'benefits', 'benefits'], ['working_method_steps', 'workingMethodSteps', 'working_method_steps']];
    }

    public function test_empty_browser_collections_remove_all_items(): void
    {
        $admin = $this->cmsAdmin();
        $payload = [...$this->payload(), 'benefits' => '', 'working_method_steps' => ''];

        $this->actingAs($admin)->put('/admin/about', $payload)->assertRedirect('/admin/about');

        $this->assertDatabaseCount('about_benefits', 0);
        $this->assertDatabaseCount('working_method_steps', 0);
    }

    public function test_about_image_is_replaced_and_old_file_removed(): void
    {
        $admin = $this->cmsAdmin();
        $payload = [...$this->payload(), 'image' => UploadedFile::fake()->image('about.png', 900, 600)];

        $this->actingAs($admin)->put('/admin/about', $payload)->assertRedirect('/admin/about');

        Storage::disk('public')->assertMissing('seed/about.png');
        Storage::disk('public')->assertExists(AboutPage::findOrFail(1)->image_path);
    }

    public function test_about_rejects_non_image_without_changing_content(): void
    {
        $admin = $this->cmsAdmin();
        $payload = [...$this->payload(), 'image' => UploadedFile::fake()->create('file.pdf'), 'title' => $this->bilingual('No desat', 'No guardado')];

        $this->actingAs($admin)->from('/admin/about')->put('/admin/about', $payload)->assertRedirect('/admin/about')->assertSessionHasErrors(['image' => 'El camp imatge principal ha de ser una imatge.']);

        $this->assertSame('SOBRE MI', AboutPage::findOrFail(1)->title['ca']);
        Storage::disk('public')->assertExists('seed/about.png');
        $this->withCookie(config('session.cookie'), session()->getId())->get('/admin/about')->assertSee('El camp imatge principal ha de ser una imatge.');
    }

    public function test_about_form_displays_field_errors_and_retains_submitted_text(): void
    {
        $admin = $this->cmsAdmin();
        $errors = ['default' => ['format' => ':message', 'messages' => ['image' => ['El camp imatge principal ha de ser una imatge.']]]];

        $this->actingAs($admin)->withSession(['errors' => $errors, '_old_input' => ['title' => ['ca' => 'Text no desat', 'es' => 'Texto no guardado']]])
            ->get('/admin/about')->assertSee('El camp imatge principal ha de ser una imatge.')->assertSee('role="alert"', false)->assertSee('Text no desat');
    }

    public function test_foreign_benefit_id_is_rejected(): void
    {
        $admin = $this->cmsAdmin();
        $foreign = AboutBenefit::factory()->create();
        $payload = $this->payload();
        $payload['benefits'][0]['id'] = $foreign->id;

        $this->actingAs($admin)->put('/admin/about', $payload)->assertSessionHasErrors('benefits.0.id');

        $this->assertSame('Text de prova', $foreign->fresh()->content['ca']);
        $this->assertSame(3, AboutPage::findOrFail(1)->benefits()->count());
    }

    public function test_missing_spanish_translation_is_rejected(): void
    {
        $admin = $this->cmsAdmin();
        $payload = [...$this->payload(), 'title' => ['ca' => 'Nou títol']];

        $this->actingAs($admin)->put('/admin/about', $payload)->assertSessionHasErrors('title.es');

        $this->assertSame('SOBRE MI', AboutPage::findOrFail(1)->title['ca']);
    }
}
