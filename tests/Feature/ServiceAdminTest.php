<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceFeature;
use App\Models\ServicePage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function payload(Service $service): array
    {
        return [...$this->contentPayload($service), 'features' => $service->features->map(fn ($item) => $item->only(['id', 'content', 'sort_order']))->all()];
    }

    public function test_admin_can_create_service_and_features_without_technical_identifiers(): void
    {
        $admin = $this->cmsAdmin();
        $payload = ['title' => $this->bilingual('Nou servei', 'Nuevo servicio'), 'subtitle' => null, 'description' => $this->bilingual(), 'active' => 1, 'sort_order' => 0,
            'features' => [['content' => $this->bilingual('Característica', 'Característica'), 'sort_order' => 0]], 'icon' => UploadedFile::fake()->image('icon.png')];

        $this->actingAs($admin)->post('/admin/services', $payload)->assertSessionHas('status');

        $service = Service::where('title->ca', 'Nou servei')->firstOrFail();
        $this->assertSame('Nuevo servicio', $service->title['es']);
        $this->assertNull($service->subtitle);
        $this->assertSame(1, $service->features()->count());
        Storage::disk('public')->assertExists($service->icon_path);
        $this->getJson('/api/v1/services?locale=es')->assertJsonCount(5, 'data');
    }

    public function test_long_service_titles_receive_a_bounded_generated_slug(): void
    {
        $admin = $this->cmsAdmin();
        $title = str_repeat('Servei ', 100);
        $this->actingAs($admin)->post('/admin/services', [
            'title' => $this->bilingual($title, $title), 'description' => $this->bilingual(),
            'active' => 1, 'sort_order' => 0, 'features' => [],
        ])->assertSessionHas('status');

        $this->assertLessThanOrEqual(89, strlen(Service::latest('id')->firstOrFail()->slug));
    }

    public function test_admin_can_update_service_deactivate_it_and_keep_stable_slug_and_media(): void
    {
        $admin = $this->cmsAdmin();
        $service = Service::where('slug', 'advice')->firstOrFail();
        $payload = [...$this->payload($service), 'title' => $this->bilingual('Consultoria nova', 'Consultoría nueva'), 'active' => 0, 'slug' => 'injected'];

        $this->actingAs($admin)->put('/admin/services/'.$service->id, $payload)->assertRedirect('/admin/services/'.$service->id.'/edit');

        $this->assertFalse($service->fresh()->active);
        $this->assertSame('advice', $service->fresh()->slug);
        $this->assertSame('Consultoría nueva', $service->fresh()->title['es']);
        Storage::disk('public')->assertExists('seed/service-advice.svg');
        $this->getJson('/api/v1/services')->assertJsonCount(3, 'data')->assertJsonMissing(['slug' => 'advice']);
    }

    public function test_features_can_be_added_updated_removed_and_reordered(): void
    {
        $admin = $this->cmsAdmin();
        $service = Service::where('slug', 'advice')->firstOrFail();
        $feature = $service->features()->firstOrFail();
        $payload = $this->payload($service);
        $payload['features'] = [['id' => $feature->id, 'content' => $this->bilingual('Actualitzada', 'Actualizada'), 'sort_order' => 4], ['content' => $this->bilingual('Nova', 'Nueva'), 'sort_order' => 0]];

        $this->actingAs($admin)->put('/admin/services/'.$service->id, $payload)->assertRedirect();

        $this->assertSame(2, $service->features()->count());
        $this->assertSame('Actualizada', $feature->fresh()->content['es']);
        $this->getJson('/api/v1/services?locale=es')->assertJsonPath('data.0.features.0.content', 'Nueva')->assertJsonPath('data.0.features.1.content', 'Actualizada');
    }

    public function test_services_can_be_reordered(): void
    {
        $admin = $this->cmsAdmin();
        $services = Service::orderBy('id')->get();
        $items = $services->map(fn ($service, $index) => ['id' => $service->id, 'sort_order' => 3 - $index])->all();

        $this->actingAs($admin)->put('/admin/services/order', compact('items'))->assertRedirect('/admin/services');

        $this->assertDatabaseHas('services', ['slug' => 'research', 'sort_order' => 0]);
        $this->getJson('/api/v1/services')->assertJsonPath('data.0.slug', 'research')->assertJsonPath('data.3.slug', 'advice');
    }

    public function test_deletion_requires_confirmation_and_cascades_features(): void
    {
        $admin = $this->cmsAdmin();
        $service = Service::where('slug', 'advice')->firstOrFail();

        $this->actingAs($admin)->delete('/admin/services/'.$service->id, [])->assertSessionHasErrors('confirm_delete');
        $this->assertModelExists($service);
        $this->delete('/admin/services/'.$service->id, ['confirm_delete' => 1])->assertRedirect('/admin/services');

        $this->assertModelMissing($service);
        $this->assertDatabaseMissing('service_features', ['service_id' => $service->id]);
        Storage::disk('public')->assertMissing('seed/service-advice.svg');
    }

    public function test_features_from_other_services_cannot_be_reassigned(): void
    {
        $admin = $this->cmsAdmin();
        $service = Service::where('slug', 'advice')->firstOrFail();
        $foreign = ServiceFeature::factory()->create();
        $payload = $this->payload($service);
        $payload['features'][0]['id'] = $foreign->id;

        $this->actingAs($admin)->put('/admin/services/'.$service->id, $payload)->assertSessionHasErrors('features.0.id');

        $this->assertSame(3, $service->features()->count());
        $this->assertSame($foreign->service_id, $foreign->fresh()->service_id);
    }

    public function test_invalid_service_translation_boolean_and_order_are_rejected(): void
    {
        $admin = $this->cmsAdmin();
        $service = Service::where('slug', 'advice')->firstOrFail();
        $payload = [...$this->payload($service), 'title' => ['ca' => 'Títol'], 'active' => 'invalid', 'sort_order' => -1];

        $this->actingAs($admin)->put('/admin/services/'.$service->id, $payload)->assertSessionHasErrors(['title.es', 'active', 'sort_order']);

        $this->assertTrue($service->fresh()->active);
        $this->assertSame('Consultoria i Assessorament Especialitzat', $service->fresh()->title['ca']);
    }

    public function test_services_page_editor_updates_public_page_metadata_and_preserves_shared_image(): void
    {
        $admin = $this->cmsAdmin();
        $page = ServicePage::findOrFail(1);
        $payload = [...$this->contentPayload($page), 'lead' => $this->bilingual('Nova presentació', 'Nueva presentación'), 'image' => UploadedFile::fake()->image('services.png')];

        $this->actingAs($admin)->put('/admin/services/page', $payload)->assertRedirect('/admin/services/page');

        Storage::disk('public')->assertExists('seed/documents.png');
        Storage::disk('public')->assertExists($page->fresh()->image_path);
        $this->getJson('/api/v1/services?locale=es')->assertJsonPath('meta.page.lead', 'Nueva presentación');
    }

    public function test_invalid_reorder_does_not_partially_change_positions(): void
    {
        $admin = $this->cmsAdmin();
        $items = [['id' => 1, 'sort_order' => 9], ['id' => 999999, 'sort_order' => 0]];

        $this->actingAs($admin)->put('/admin/services/order', compact('items'))->assertSessionHasErrors('items.1.id');

        $this->assertDatabaseHas('services', ['id' => 1, 'sort_order' => 0]);
    }

    public function test_duplicate_repeated_feature_id_is_rejected(): void
    {
        $admin = $this->cmsAdmin();
        $service = Service::findOrFail(1);
        $payload = $this->payload($service);
        $payload['features'][1]['id'] = $payload['features'][0]['id'];

        $this->actingAs($admin)->put('/admin/services/1', $payload)->assertSessionHasErrors('features.0.id');

        $this->assertSame(3, $service->features()->count());
    }

    public function test_service_forms_and_list_render_with_optional_translated_fields(): void
    {
        $admin = $this->cmsAdmin();
        $service = Service::where('slug', 'organization')->firstOrFail();

        $this->actingAs($admin)->get('/admin/services')->assertOk()->assertSee('Afegeix un servei');
        $this->get('/admin/services/create')->assertOk()->assertSee('Subtítol (opcional)')->assertSee('Castellano');
        $this->get('/admin/services/'.$service->id.'/edit')->assertOk()->assertSee('Característiques del servei');
        $this->get('/admin/services/page')->assertOk()->assertSee('Presentació de Serveis');
    }
}
