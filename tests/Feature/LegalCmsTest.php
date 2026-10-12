<?php

namespace Tests\Feature;

use App\Models\LegalPage;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\LegalPageSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegalCmsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function sections(): array
    {
        return [['legal-notice', 'legal_notice'], ['privacy', 'privacy_policy'], ['cookies', 'cookie_policy']];
    }

    #[DataProvider('sections')]
    public function test_guests_cannot_read_or_update_legal_admin(string $section, string $type): void
    {
        $page = LegalPage::factory()->create(['type' => $type]);
        $this->get('/admin/legal/'.$section)->assertRedirect('/admin/login');
        $this->put('/admin/legal/'.$section, ['active' => 1])->assertRedirect('/admin/login');
        $this->assertFalse($page->fresh()->active);
    }

    #[DataProvider('sections')]
    public function test_admin_can_edit_publish_and_read_each_localized_page(string $section, string $type): void
    {
        $page = LegalPage::factory()->create(['type' => $type]);
        $admin = User::factory()->administrator()->create();
        $payload = ['title' => $this->bilingual('Títol CA', 'Título ES'), 'body' => $this->bilingual("Text CA\n\nParàgraf", "Texto ES\n\nPárrafo"), 'active' => 1, 'type' => 'injected'];
        $this->actingAs($admin)->get('/admin/legal/'.$section)->assertOk()->assertSee('Català')->assertSee('Castellano');
        $this->put('/admin/legal/'.$section, $payload)->assertRedirect('/admin/legal/'.$section);
        $this->assertSame($payload['title'], $page->fresh()->title);
        $this->assertSame($payload['body'], $page->fresh()->body);
        $this->assertSame($type, $page->fresh()->type);
        $this->assertTrue($page->fresh()->active);
        $this->getJson('/api/v1/legal/'.$section)->assertOk()->assertHeader('Content-Language', 'ca')->assertExactJson(['data' => ['type' => $type, 'title' => 'Títol CA', 'body' => "Text CA\n\nParàgraf", 'body_html' => '<p>Text CA</p><p>Paràgraf</p>']]);
        $this->getJson('/api/v1/legal/'.$section.'?locale=ca')->assertOk()->assertJsonPath('data.title', 'Títol CA');
        $this->getJson('/api/v1/legal/'.$section.'?locale=es')->assertOk()->assertHeader('Content-Language', 'es')->assertExactJson(['data' => ['type' => $type, 'title' => 'Título ES', 'body' => "Texto ES\n\nPárrafo", 'body_html' => '<p>Texto ES</p><p>Párrafo</p>']]);
    }

    #[DataProvider('sections')]
    public function test_inactive_pages_return_404(string $section, string $type): void
    {
        LegalPage::factory()->create(['type' => $type]);
        $this->getJson('/api/v1/legal/'.$section)->assertNotFound();
    }

    public function test_non_admin_is_forbidden_and_unknown_sections_are_absent(): void
    {
        $page = LegalPage::factory()->create();
        $this->actingAs(User::factory()->create())->get('/admin/legal/legal-notice')->assertForbidden();
        $this->put('/admin/legal/legal-notice', ['active' => 1])->assertForbidden();
        $this->assertFalse($page->fresh()->active);
        $this->getJson('/api/v1/legal/terms')->assertNotFound();
        $this->getJson('/api/v1/blog')->assertNotFound();
        $this->getJson('/api/v1/legal/privacy')->assertNotFound();
        $this->postJson('/api/v1/legal/legal-notice', [])->assertMethodNotAllowed();
    }

    public function test_activation_requires_both_languages_and_valid_boolean(): void
    {
        $page = LegalPage::factory()->create();
        $this->actingAs(User::factory()->administrator()->create());
        $payload = ['title' => ['ca' => 'Títol'], 'body' => ['ca' => null, 'es' => null], 'active' => 1];
        $this->put('/admin/legal/legal-notice', $payload)->assertSessionHasErrors(['title.es', 'body.ca', 'body.es']);
        $this->put('/admin/legal/legal-notice', [...$payload, 'active' => 'yes'])->assertSessionHasErrors('active');
        $this->putJson('/admin/legal/legal-notice', [...$payload, 'active' => true])->assertUnprocessable()->assertJsonValidationErrors(['body.ca', 'body.es']);
        $this->assertFalse($page->fresh()->active);
    }

    public function test_blank_drafts_can_be_saved_and_published_pages_can_be_withdrawn(): void
    {
        $page = LegalPage::factory()->create(['active' => true]);
        $payload = ['title' => $page->title, 'body' => ['ca' => null, 'es' => null], 'active' => 0];
        $this->actingAs(User::factory()->administrator()->create())->put('/admin/legal/legal-notice', $payload)->assertSessionHasNoErrors();
        $this->assertSame($payload['body'], $page->fresh()->body);
        $this->assertFalse($page->fresh()->active);
        $this->getJson('/api/v1/legal/legal-notice')->assertNotFound();
    }

    public function test_unsafe_only_html_and_extra_translation_keys_are_rejected_and_legacy_input_is_escaped(): void
    {
        $page = LegalPage::factory()->create();
        DB::table('legal_pages')->where('id', $page->id)->update(['body' => json_encode($this->bilingual('</textarea><script>alert(1)</script>', '<img src=x onerror=alert(1)>'))]);
        $page->refresh();
        $this->actingAs(User::factory()->administrator()->create());
        $this->get('/admin/legal/legal-notice')->assertSee('</textarea><script>alert(1)</script>')->assertDontSee('</textarea><script>alert(1)</script>', false);
        $original = $page->body;
        $this->put('/admin/legal/legal-notice', ['title' => [...$page->title, 'en' => 'Unexpected'], 'body' => $page->body, 'active' => 1])->assertSessionHasErrors(['title', 'body.ca', 'body.es']);
        $this->assertSame($original, $page->fresh()->body);
        $this->assertFalse($page->fresh()->active);
    }

    public function test_titles_and_bodies_enforce_length_limits(): void
    {
        $page = LegalPage::factory()->create();
        $this->actingAs(User::factory()->administrator()->create())->put('/admin/legal/legal-notice', ['title' => $this->bilingual(str_repeat('a', 256), str_repeat('a', 256)), 'body' => $this->bilingual(str_repeat('b', 100001), str_repeat('b', 100001)), 'active' => 0])->assertSessionHasErrors(['title.ca', 'title.es', 'body.ca', 'body.es']);
        $this->assertSame('Títol de prova', $page->fresh()->title['ca']);
    }

    public function test_unsupported_and_array_locales_return_422(): void
    {
        LegalPage::factory()->create(['active' => true]);
        $this->getJson('/api/v1/legal/legal-notice?locale=en')->assertUnprocessable();
        $this->getJson('/api/v1/legal/legal-notice?locale[]=ca')->assertUnprocessable();
    }

    public function test_seeds_are_empty_inactive_and_preserve_client_edits(): void
    {
        $this->seed(LegalPageSeeder::class);
        $this->assertDatabaseCount('legal_pages', 3);
        $page = LegalPage::where('type', 'privacy_policy')->firstOrFail();
        $this->assertSame(['ca' => null, 'es' => null], $page->body);
        $this->assertFalse($page->active);
        $page->update(['body' => $this->bilingual(), 'active' => true]);
        $this->seed(LegalPageSeeder::class);
        $this->assertTrue($page->fresh()->active);
        $this->assertSame($this->bilingual(), $page->fresh()->body);
    }

    public function test_legal_identity_is_nullable_editable_validated_and_private(): void
    {
        $admin = $this->cmsAdmin();
        $settings = SiteSetting::findOrFail(1);
        $this->assertNull($settings->tax_identifier);
        $payload = ['legal_name' => 'Nom de prova', 'tax_identifier' => 'Identificador pendent', 'postal_address' => 'Adreça de prova', 'privacy_contact_email' => 'privacy@example.test', 'professional_registration' => null, 'data_controller_name' => null];
        $this->actingAs($admin)->put('/admin/settings', $payload)->assertSessionHasNoErrors();
        $this->assertSame($payload, $settings->fresh()->only(array_keys($payload)));
        $this->getJson('/api/v1/site')->assertJsonMissingPath('data.tax_identifier')->assertJsonMissingPath('data.privacy_contact_email')->assertJsonMissingPath('data.legal_name');
        $this->put('/admin/settings', ['privacy_contact_email' => 'invalid', 'tax_identifier' => ['invalid']])->assertSessionHasErrors(['privacy_contact_email', 'tax_identifier']);
        $this->assertSame('privacy@example.test', $settings->fresh()->privacy_contact_email);
        $this->get('/admin/settings')->assertSee('Identitat legal');
    }
}
