<?php

namespace Tests\Feature;

use App\Models\ContactPage;
use App\Models\ContactServiceOption;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ContactPageAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function optionPayload(ContactServiceOption $option): array
    {
        return $option->only(['label', 'active', 'sort_order']);
    }

    public function test_admin_can_update_all_contact_copy(): void
    {
        $admin = $this->cmsAdmin();
        $page = ContactPage::findOrFail(1);
        $payload = [...$this->contentPayload($page), 'content' => $this->bilingual('Explica el projecte', 'Explica el proyecto')];

        $this->actingAs($admin)->put('/admin/contact', $payload)->assertRedirect('/admin/contact');

        $this->assertSame(['ca' => 'Explica el projecte', 'es' => 'Explica el proyecto'], $page->fresh()->content);
        $this->assertDatabaseCount('contact_service_options', 6);
        $this->getJson('/api/v1/pages/contact?locale=es')->assertJsonPath('data.content', 'Explica el proyecto');
    }

    public function test_admin_can_create_contact_option_with_generated_stable_identifier(): void
    {
        $admin = $this->cmsAdmin();
        $payload = ['label' => $this->bilingual('Opció nova', 'Opción nueva'), 'active' => 1, 'sort_order' => 10];

        $this->actingAs($admin)->post('/admin/contact/options', $payload)->assertSessionHas('status');

        $this->assertDatabaseCount('contact_service_options', 7);
        $option = ContactServiceOption::where('label->ca', 'Opció nova')->firstOrFail();
        $this->assertSame(1, $option->contact_page_id);
        $this->assertSame('Opción nueva', $option->label['es']);
        $this->getJson('/api/v1/pages/contact?locale=es')->assertJsonPath('data.service_options.6.label', 'Opción nueva');
    }

    public function test_admin_can_update_deactivate_and_keep_option_identifier(): void
    {
        $admin = $this->cmsAdmin();
        $option = ContactServiceOption::where('slug', 'organization')->firstOrFail();
        $payload = [...$this->optionPayload($option), 'label' => $this->bilingual('Arxius', 'Archivos'), 'active' => 0, 'slug' => 'injected', 'contact_page_id' => 999];

        $this->actingAs($admin)->put('/admin/contact/options/'.$option->id, $payload)->assertRedirect('/admin/contact/options/'.$option->id.'/edit');

        $this->assertFalse($option->fresh()->active);
        $this->assertSame('organization', $option->fresh()->slug);
        $this->assertSame('Archivos', $option->fresh()->label['es']);
        $this->getJson('/api/v1/pages/contact')->assertJsonCount(5, 'data.service_options')->assertJsonMissing(['slug' => 'organization']);
    }

    public function test_contact_options_can_be_reordered(): void
    {
        $admin = $this->cmsAdmin();
        $items = ContactServiceOption::orderBy('id')->get()->map(fn ($option, $index) => ['id' => $option->id, 'sort_order' => 5 - $index])->all();

        $this->actingAs($admin)->put('/admin/contact/options/order', compact('items'))->assertRedirect('/admin/contact');

        $this->getJson('/api/v1/pages/contact')->assertJsonPath('data.service_options.0.slug', 'other')->assertJsonPath('data.service_options.5.slug', 'organization');
    }

    public function test_contact_option_can_be_deleted_only_with_confirmation(): void
    {
        $admin = $this->cmsAdmin();
        $option = ContactServiceOption::where('slug', 'other')->firstOrFail();

        $this->actingAs($admin)->delete('/admin/contact/options/'.$option->id, [])->assertSessionHasErrors('confirm_delete');
        $this->assertModelExists($option);
        $this->delete('/admin/contact/options/'.$option->id, ['confirm_delete' => 1])->assertRedirect('/admin/contact');

        $this->assertModelMissing($option);
        $this->getJson('/api/v1/pages/contact')->assertJsonCount(5, 'data.service_options');
    }

    public function test_option_validation_prevents_missing_translations_and_negative_order(): void
    {
        $admin = $this->cmsAdmin();

        $this->actingAs($admin)->post('/admin/contact/options', ['label' => ['ca' => 'Opció'], 'sort_order' => -1, 'active' => 'yes'])->assertSessionHasErrors(['label.es', 'sort_order', 'active']);

        $this->assertDatabaseCount('contact_service_options', 6);
    }

    public function test_options_from_other_contact_pages_cannot_be_edited_or_reordered(): void
    {
        $admin = $this->cmsAdmin();
        $foreign = ContactServiceOption::factory()->create();

        $this->actingAs($admin)->get('/admin/contact/options/'.$foreign->id.'/edit')->assertNotFound();
        $this->put('/admin/contact/options/'.$foreign->id, $this->optionPayload($foreign))->assertNotFound();
        $this->delete('/admin/contact/options/'.$foreign->id, ['confirm_delete' => 1])->assertNotFound();
        $this->put('/admin/contact/options/order', ['items' => [['id' => $foreign->id, 'sort_order' => 2]]])->assertSessionHasErrors('items.0.id');

        $this->assertModelExists($foreign);
        $this->assertSame(0, $foreign->fresh()->sort_order);
    }

    public function test_contact_option_create_and_edit_forms_render(): void
    {
        $admin = $this->cmsAdmin();
        $option = ContactServiceOption::firstOrFail();

        $this->actingAs($admin)->get('/admin/contact')->assertOk()->assertSee('Opcions de serveis del formulari');
        $this->get('/admin/contact/options/create')->assertOk()->assertSee('Català')->assertSee('Castellano');
        $this->get('/admin/contact/options/'.$option->id.'/edit')->assertOk()->assertSee('Desa l’opció');
    }
}
